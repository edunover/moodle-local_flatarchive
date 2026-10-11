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
 * Static lesson navigation, authored media and permission boundaries.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\lesson_exporter
 */
final class lesson_exporter_test extends \advanced_testcase {
    /** Set up isolated Moodle data. */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Create a lesson and a deliberately non-ID-ordered page chain.
     *
     * @return array Lesson and ordered page IDs.
     */
    private function fixture(): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', ['course' => $course->id, 'custom' => 1]);
        $ids = [];
        foreach ([3, 20, 2] as $type) {
            $ids[] = $DB->insert_record('lesson_pages', ['lessonid' => $lesson->id, 'qtype' => $type,
                'title' => 'Été & page ' . $type, 'contents' => '<p>Content ' . $type . '</p>', 'contentsformat' => FORMAT_HTML]);
        }
        $order = [$ids[1], $ids[0], $ids[2]];
        foreach ($order as $position => $id) {
            $DB->update_record('lesson_pages', ['id' => $id, 'prevpageid' => $order[$position - 1] ?? 0,
                'nextpageid' => $order[$position + 1] ?? 0]);
        }
        return [$lesson, $order];
    }

    /**
     * Add an authored answer.
     *
     * @param \stdClass $lesson Lesson.
     * @param int $page Page ID.
     * @param int $jump Destination.
     * @return int Answer ID.
     */
    private function answer(\stdClass $lesson, int $page, int $jump): int {
        global $DB;
        return $DB->insert_record('lesson_answers', ['lessonid' => $lesson->id, 'pageid' => $page,
            'jumpto' => $jump, 'answer' => '<p>Answer</p>', 'answerformat' => FORMAT_HTML,
            'response' => '<p>Feedback</p>', 'responseformat' => FORMAT_HTML, 'score' => 1]);
    }

    /**
     * Export to a fresh directory.
     *
     * @param \stdClass $lesson Lesson.
     * @return array Export metadata and path.
     */
    private function export(\stdClass $lesson): array {
        $info = get_fast_modinfo($lesson->course);
        $directory = make_request_directory();
        return [lesson_exporter::render($info->get_cm($lesson->cmid), $info, $directory), $directory];
    }

    /** Fixed jumps are local, ordered, escaped and do not write lesson state. */
    public function test_graph_media_and_no_attempt_changes(): void {
        global $DB;
        [$lesson, $ids] = $this->fixture();
        $first = $this->answer($lesson, $ids[0], -1);
        foreach ([$ids[2], 0, -40, -9] as $jump) {
            $this->answer($lesson, $ids[1], $jump);
        }
        $this->answer($lesson, $ids[2], -1);
        $context = \context_module::instance($lesson->cmid);
        $DB->set_field('lesson_pages', 'contents', '<img src="@@PLUGINFILE@@/image.png" alt="Test">', ['id' => $ids[0]]);
        $DB->set_field('lesson_answers', 'answer', '<img src="@@PLUGINFILE@@/image.png" alt="Answer">', ['id' => $first]);
        $DB->set_field('lesson_answers', 'response', '<img src="@@PLUGINFILE@@/image.png" alt="Feedback">', ['id' => $first]);
        foreach (['page_contents' => $ids[0], 'page_answers' => $first, 'page_responses' => $first, 'mediafile' => 0,
                'essay_answers' => 999] as $area => $id) {
            get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'mod_lesson',
                'filearea' => $area, 'itemid' => $id, 'filepath' => '/', 'filename' => 'image.png'], $area);
        }
        $before = $DB->get_records('lesson_pages', ['lessonid' => $lesson->id]);
        [$export, $directory] = $this->export($lesson);
        $this->assertCount(4, $export['filenames']);
        $index = file_get_contents($directory . '/' . $export['filename']);
        $prefix = 'lesson-' . $lesson->cmid;
        $this->assertLessThan(strpos($index, $prefix . '-page-' . $ids[1]), strpos($index, $prefix . '-page-' . $ids[0]));
        $this->assertStringContainsString('Été &amp; page', $index);
        $page = file_get_contents($directory . '/' . $prefix . '-page-' . $ids[1] . '.html');
        foreach ($ids as $id) {
            $this->assertStringContainsString('href="' . $prefix . '-page-' . $id . '.html"', $page);
        }
        $this->assertStringContainsString('href="' . $prefix . '.html#end"', $page);
        foreach (['page_contents' => $ids[0], 'page_answers' => $first, 'page_responses' => $first, 'mediafile' => 0] as $area => $id) {
            $path = $directory . '/files/' . $lesson->cmid . '/' . $area . '/' . $id . '/image.png';
            $this->assertSame($area, file_get_contents($path));
        }
        $this->assertDirectoryDoesNotExist($directory . '/files/' . $lesson->cmid . '/essay_answers');
        foreach ($export['filenames'] as $file) {
            $html = file_get_contents($directory . '/' . $file);
            $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
            $this->assertStringNotContainsString('<form', $html);
        }
        $this->assertEquals($before, $DB->get_records('lesson_pages', ['lessonid' => $lesson->id]));
        $this->assertSame(0, $DB->count_records('lesson_attempts', ['lessonid' => $lesson->id]));
        $this->assertSame(0, $DB->count_records('lesson_grades', ['lessonid' => $lesson->id]));
        $this->assertSame(0, $DB->count_records('lesson_timer', ['lessonid' => $lesson->id]));
    }

    /** Random jumps and foreign destinations must not become invented links. */
    public function test_dynamic_and_foreign_destinations(): void {
        [$lesson, $ids] = $this->fixture();
        [$other, $otherids] = $this->fixture();
        foreach ([-50, -60, -70, -80, -99, $otherids[0]] as $jump) {
            $this->answer($lesson, $ids[0], $jump);
        }
        [$export, $directory] = $this->export($lesson);
        $html = file_get_contents($directory . '/lesson-' . $lesson->cmid . '-page-' . $ids[0] . '.html');
        $this->assertSame(5, substr_count($html, get_string('lessondynamic', 'local_flatarchive')));
        $this->assertStringContainsString(get_string('lessonmissing', 'local_flatarchive'), $html);
        $this->assertStringNotContainsString('-page-' . $otherids[0] . '.html', $html);
    }

    /** Cycles and orphan pages must terminate without repairing the source records. */
    public function test_broken_order_is_bounded(): void {
        $pages = [8 => (object)['id' => 8, 'prevpageid' => 9, 'nextpageid' => 9],
            9 => (object)['id' => 9, 'prevpageid' => 8, 'nextpageid' => 8],
            10 => (object)['id' => 10, 'prevpageid' => 999, 'nextpageid' => 0]];
        $this->assertSame([10, 8, 9], array_keys(lesson_exporter::ordered_pages($pages)));
    }

    /** Reading or managing attempts does not authorise bulk export of solutions. */
    public function test_non_editor_is_denied_before_file_copy(): void {
        [$lesson, $ids] = $this->fixture();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $lesson->course, 'teacher');
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        $this->export($lesson);
    }

    /** All core page types retain their content, including matching pairs and essays. */
    public function test_core_types_and_matching(): void {
        global $DB;
        [$lesson, $ids] = $this->fixture();
        foreach ([1, 2, 3, 5, 8, 10, 20, 21, 30, 31] as $type) {
            $id = $DB->insert_record('lesson_pages', ['lessonid' => $lesson->id, 'qtype' => $type,
                'title' => 'Type ' . $type, 'contents' => 'Type content ' . $type, 'contentsformat' => FORMAT_HTML]);
            $this->answer($lesson, $id, -9);
            if ($type === 5) {
                $this->answer($lesson, $id, 0);
                $this->answer($lesson, $id, 0);
                $matchingid = $id;
            }
        }
        [$export, $directory] = $this->export($lesson);
        $this->assertCount(14, $export['filenames']);
        $html = file_get_contents($directory . '/lesson-' . $lesson->cmid . '-page-' . $matchingid . '.html');
        $this->assertStringContainsString(get_string('lessonpair', 'local_flatarchive'), $html);
        $this->assertStringContainsString(get_string('lessoncorrect', 'local_flatarchive'), $html);
        $this->assertStringContainsString(get_string('lessonincorrect', 'local_flatarchive'), $html);
        $this->assertSame(1, substr_count($html, get_string('lessonmatch', 'local_flatarchive')));
    }

    /** Flash-card next-page behaviour must not be presented as a fixed path. */
    public function test_flashcards(): void {
        global $DB;
        [$lesson, $ids] = $this->fixture();
        $DB->set_field('lesson', 'nextpagedefault', 1, ['id' => $lesson->id]);
        $this->answer($lesson, $ids[1], -1);
        [$export, $directory] = $this->export($lesson);
        $html = file_get_contents($directory . '/lesson-' . $lesson->cmid . '-page-' . $ids[1] . '.html');
        $this->assertStringContainsString(get_string('lessondynamic', 'local_flatarchive'), $html);
        $this->assertStringNotContainsString('-page-' . $ids[2] . '.html', $html);
    }

    /** An explicit view prohibition remains effective for editors. */
    public function test_view_prohibition(): void {
        global $DB;
        [$lesson, $ids] = $this->fixture();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $lesson->course, 'editingteacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('mod/lesson:view', CAP_PROHIBIT, $role, \context_module::instance($lesson->cmid)->id);
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        $this->export($lesson);
    }

    /** Empty lessons produce a valid index. */
    public function test_empty_lesson(): void {
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', ['course' => $course->id]);
        [$export, $directory] = $this->export($lesson);
        $this->assertCount(1, $export['filenames']);
        $this->assertStringContainsString(get_string('lessonempty', 'local_flatarchive'),
            file_get_contents($directory . '/' . $export['filename']));
    }
}
