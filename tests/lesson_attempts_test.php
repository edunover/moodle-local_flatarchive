<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_flatarchive;

use local_flatarchive\archive\lesson_exporter;

/**
 * Attempt report privacy, retry separation and essay media.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\lesson_attempts
 */
final class lesson_attempts_test extends \advanced_testcase {
    /**
     * Create isolated fixtures.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Create an essay with two retries and private media.
     *
     * @return array Lesson, student and attempt IDs.
     */
    private function fixture(): array {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['groupmode' => SEPARATEGROUPS]);
        $lesson = $generator->create_module('lesson', ['course' => $course->id, 'groupmode' => SEPARATEGROUPS]);
        $student = $generator->create_user(['firstname' => 'VisibleStudent', 'lastname' => 'Lesson']);
        $generator->enrol_user($student->id, $course->id, 'student');
        $page = $DB->insert_record('lesson_pages', ['lessonid' => $lesson->id, 'qtype' => 10,
            'title' => 'Essay', 'contents' => 'Explain', 'contentsformat' => FORMAT_HTML]);
        $context = \context_module::instance($lesson->cmid);
        $ids = [];
        foreach ([0, 1] as $retry) {
            $essay = (object)['answer' => '<p>PrivateEssay' . $retry . '</p><img src="@@PLUGINFILE@@/image.png" alt="Test">',
                'answerformat' => FORMAT_HTML, 'response' => 'TeacherFeedback' . $retry,
                'responseformat' => FORMAT_HTML, 'score' => 7, 'graded' => $retry === 0];
            $id = $DB->insert_record('lesson_attempts', ['lessonid' => $lesson->id, 'userid' => $student->id,
                'pageid' => $page, 'retry' => $retry, 'useranswer' => serialize($essay), 'timeseen' => 1700000010 + $retry * 100]);
            $DB->insert_record('lesson_timer', ['lessonid' => $lesson->id, 'userid' => $student->id,
                'starttime' => 1700000000 + $retry * 100, 'lessontime' => 1700000020 + $retry * 100, 'completed' => $retry === 0]);
            get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'mod_lesson',
                'filearea' => 'essay_answers', 'itemid' => $id, 'filepath' => '/',
                'filename' => 'image.png'], 'PrivateMedia' . $retry);
            $ids[] = $id;
        }
        $DB->insert_record('lesson_grades', ['lessonid' => $lesson->id, 'userid' => $student->id,
            'grade' => 70, 'completed' => 1700000020]);
        return [$lesson, $student, $ids];
    }

    /**
     * Read all generated HTML.
     *
     * @param \stdClass $lesson Lesson.
     * @return array HTML, directory and export metadata.
     */
    private function export(\stdClass $lesson): array {
        $info = get_fast_modinfo($lesson->course);
        $directory = make_request_directory();
        $export = lesson_exporter::render($info->get_cm($lesson->cmid), $info, $directory);
        $html = '';
        foreach ($export['filenames'] as $file) {
            $html .= file_get_contents($directory . '/' . $file);
        }
        return [$html, $directory, $export];
    }

    /**
     * Reports retain separate retries, saved marks, pending essays and local media without writes.
     */
    public function test_attempts_and_essay_media(): void {
        global $DB;
        [$lesson, $student, $ids] = $this->fixture();
        $before = $DB->get_records('lesson_attempts', ['lessonid' => $lesson->id]);
        [$html, $directory, $export] = $this->export($lesson);
        $this->assertCount(4, $export['filenames']);
        $first = file_get_contents($directory . '/lesson-' . $lesson->cmid . '-user-' . $student->id . '-attempt-1.html');
        $second = file_get_contents($directory . '/lesson-' . $lesson->cmid . '-user-' . $student->id . '-attempt-2.html');
        $this->assertStringContainsString('PrivateEssay0', $first);
        $this->assertStringNotContainsString('PrivateEssay1', $first);
        $this->assertStringContainsString('TeacherFeedback0', $first);
        $this->assertStringContainsString(get_string('lessonrecordedgrade', 'local_flatarchive', format_float(70, 2)), $first);
        $this->assertStringContainsString('PrivateEssay1', $second);
        $this->assertStringContainsString(get_string('lessonungraded', 'local_flatarchive'), $second);
        $this->assertStringContainsString(get_string('lessonincomplete', 'local_flatarchive'), $second);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertSame('PrivateMedia0', file_get_contents(
            $directory . '/files/' . $lesson->cmid . '/essay_answers/' . $ids[0] . '/image.png'
        ));
        $this->assertEquals($before, $DB->get_records('lesson_attempts', ['lessonid' => $lesson->id]));
        $this->assertSame(1, $DB->count_records('lesson_grades', ['lessonid' => $lesson->id]));
    }

    /**
     * Editing rights alone must not disclose attempts, identities or essay files.
     */
    public function test_report_permission_required(): void {
        global $DB;
        [$lesson, $student, $ids] = $this->fixture();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $lesson->course, 'editingteacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('mod/lesson:viewreports', CAP_PROHIBIT, $role, \context_module::instance($lesson->cmid)->id);
        $this->setUser($teacher);
        [$html, $directory, $export] = $this->export($lesson);
        $this->assertCount(2, $export['filenames']);
        $this->assertStringContainsString(get_string('lessonreportsdenied', 'local_flatarchive'), $html);
        $this->assertStringNotContainsString('VisibleStudent', $html);
        $this->assertStringNotContainsString('PrivateEssay', $html);
        $this->assertDirectoryDoesNotExist($directory . '/files/' . $lesson->cmid . '/essay_answers');
    }

    /**
     * Separate groups exclude foreign attempts and files before rendering.
     */
    public function test_separate_groups(): void {
        global $DB;
        [$lesson, $student, $ids] = $this->fixture();
        $generator = $this->getDataGenerator();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $lesson->course, 'editingteacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $role, \context_module::instance($lesson->cmid)->id);
        $own = $generator->create_group(['courseid' => $lesson->course]);
        $foreign = $generator->create_group(['courseid' => $lesson->course]);
        $generator->create_group_member(['groupid' => $own->id, 'userid' => $teacher->id]);
        $generator->create_group_member(['groupid' => $foreign->id, 'userid' => $student->id]);
        $this->setUser($teacher);
        [$html, $directory] = $this->export($lesson);
        $this->assertStringNotContainsString('PrivateEssay', $html);
        $this->assertStringNotContainsString('VisibleStudent', $html);
        $this->assertDirectoryDoesNotExist($directory . '/files/' . $lesson->cmid . '/essay_answers');
        $generator->create_group_member(['groupid' => $own->id, 'userid' => $student->id]);
        [$html] = $this->export($lesson);
        $this->assertStringContainsString('PrivateEssay', $html);
    }

    /**
     * Suspended and unenrolled students are outside the active report population.
     */
    public function test_inactive_enrolment_excluded(): void {
        global $DB;
        [$lesson, $student, $ids] = $this->fixture();
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, ['userid' => $student->id]);
        [$html, $directory] = $this->export($lesson);
        $this->assertStringNotContainsString('PrivateEssay', $html);
        $this->assertDirectoryDoesNotExist($directory . '/files/' . $lesson->cmid . '/essay_answers');
    }

    /**
     * Short responses are escaped and malformed serialized essays do not abort the archive.
     */
    public function test_escaped_and_malformed_answers(): void {
        global $DB;
        [$lesson, $student, $ids] = $this->fixture();
        $DB->set_field('lesson_attempts', 'useranswer', 'not serialized', ['id' => $ids[0]]);
        $page = $DB->insert_record('lesson_pages', ['lessonid' => $lesson->id, 'qtype' => 1,
            'title' => 'Short answer', 'contents' => 'Question', 'contentsformat' => FORMAT_HTML]);
        $DB->insert_record('lesson_attempts', ['lessonid' => $lesson->id, 'userid' => $student->id,
            'pageid' => $page, 'retry' => 0, 'useranswer' => '<script>unsafe()</script>', 'timeseen' => 1700000011]);
        [$html] = $this->export($lesson);
        $this->assertStringContainsString(get_string('lessonunreadable', 'local_flatarchive'), $html);
        $this->assertStringNotContainsString('<script>unsafe()', $html);
        $this->assertStringContainsString('&lt;script&gt;unsafe()', $html);
    }
}
