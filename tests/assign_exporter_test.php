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

use local_flatarchive\archive\assign_exporter;

/**
 * Assignment history, permissions, original files and anonymous marking coverage.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\assign_exporter
 */
final class assign_exporter_test extends \advanced_testcase {
    /**
     * Seed two attempts with online text, individual grades and feedback attachments.
     *
     * @param \stdClass $assignment Assignment
     * @param int $userid Participant, or zero for a group submission
     * @param int $groupid Group
     * @return void
     */
    private function seed(\stdClass $assignment, int $userid, int $groupid = 0): void {
        global $DB;
        $context = \context_module::instance($assignment->cmid);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $submission = $DB->insert_record('assign_submission', (object)[
                'assignment' => $assignment->id, 'userid' => $userid, 'groupid' => $groupid,
                'timecreated' => time(), 'timemodified' => time(), 'status' => 'submitted',
                'attemptnumber' => $attempt, 'latest' => (int)($attempt === 1),
            ]);
            $DB->insert_record('assignsubmission_onlinetext', (object)[
                'assignment' => $assignment->id, 'submission' => $submission,
                'onlinetext' => '<p>Response ' . $attempt . ' <img src="@@PLUGINFILE@@/image.png"></p>',
                'onlineformat' => FORMAT_HTML,
            ]);
            $areas = ['assignsubmission_file' => 'submission_files', 'assignsubmission_onlinetext' => 'submissions_onlinetext'];
            foreach ($areas as $component => $area) {
                get_file_storage()->create_file_from_string([
                    'contextid' => $context->id, 'component' => $component, 'filearea' => $area,
                    'itemid' => $submission, 'filepath' => '/', 'filename' => 'image.png',
                ], 'original bytes ' . $attempt);
            }
            if (!$userid) {
                continue;
            }
            $grade = $DB->insert_record('assign_grades', (object)[
                'assignment' => $assignment->id, 'userid' => $userid, 'timecreated' => time(), 'timemodified' => time(),
                'grader' => 2, 'grade' => 70 + $attempt, 'attemptnumber' => $attempt,
            ]);
            $DB->insert_record('assignfeedback_comments', (object)[
                'assignment' => $assignment->id, 'grade' => $grade,
                'commenttext' => '<p>Feedback ' . $attempt . '</p>', 'commentformat' => FORMAT_HTML,
            ]);
            get_file_storage()->create_file_from_string([
                'contextid' => $context->id, 'component' => 'assignfeedback_file', 'filearea' => 'feedback_files',
                'itemid' => $grade, 'filepath' => '/', 'filename' => 'feedback #1.txt',
            ], 'feedback bytes ' . $attempt);
        }
    }

    /**
     * Teachers get all stored attempts; non-graders get instructions without participant records.
     */
    public function test_history_files_and_permissions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user(['firstname' => 'Alice', 'lastname' => 'Example']);
        $generator->enrol_user($student->id, $course->id, 'student');
        $assignment = $generator->create_module('assign', [
            'course' => $course->id, 'intro' => 'Instructions', 'grade' => 100,
        ]);
        $this->seed($assignment, (int)$student->id);
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('flatarchive-assign-' . random_string());
        $result = assign_exporter::render($modinfo->get_cm($assignment->cmid), $modinfo, $directory);
        $this->assertCount(2, $result['filenames']);
        $html = file_get_contents($directory . '/' . $result['filenames'][1]);
        $this->assertStringContainsString('Alice Example', $html);
        $this->assertStringContainsString('Response 0', $html);
        $this->assertStringContainsString('Response 1', $html);
        $this->assertStringContainsString('Feedback 1', $html);
        $this->assertStringContainsString('71.00 / 100.00', $html);
        $this->assertStringContainsString('feedback%20%231.txt', $html);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
        $this->assertStringContainsString('href="index.html"', $html);
        foreach (glob($directory . '/files/' . $assignment->cmid . '/submission_files/*/image.png') as $path) {
            $this->assertStringStartsWith('original bytes ', file_get_contents($path));
        }
        $this->setUser($student);
        $modinfo = get_fast_modinfo($course);
        $studentdir = make_temp_directory('flatarchive-assign-student-' . random_string());
        $result = assign_exporter::render($modinfo->get_cm($assignment->cmid), $modinfo, $studentdir);
        $this->assertCount(1, $result['filenames']);
        $this->assertDirectoryDoesNotExist($studentdir . '/files/' . $assignment->cmid . '/submission_files');
        $this->assertStringNotContainsString('Alice Example', file_get_contents($studentdir . '/' . $result['filename']));
    }

    /**
     * Anonymous grading keeps identities out of generated reports and filenames.
     */
    public function test_anonymous_reports(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user(['firstname' => 'PrivateName', 'lastname' => 'PrivateSurname']);
        $generator->enrol_user($student->id, $course->id, 'student');
        $assignment = $generator->create_module('assign', ['course' => $course->id, 'blindmarking' => 1]);
        $this->seed($assignment, (int)$student->id);
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('flatarchive-assign-blind-' . random_string());
        $result = assign_exporter::render($modinfo->get_cm($assignment->cmid), $modinfo, $directory);
        $this->assertCount(2, $result['filenames']);
        foreach ($result['filenames'] as $file) {
            $html = file_get_contents($directory . '/' . $file);
            $this->assertStringNotContainsString('PrivateName', $html);
            $this->assertStringNotContainsString('PrivateSurname', $html);
        }
    }

    /**
     * Separate-group teachers only export their group and team files use the team submission.
     */
    public function test_separate_groups_and_team_submission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $assignment = $generator->create_module('assign', [
            'course' => $course->id, 'groupmode' => SEPARATEGROUPS, 'teamsubmission' => 1,
        ]);
        for ($i = 0; $i < 2; $i++) {
            $group = $generator->create_group(['courseid' => $course->id]);
            $student = $generator->create_user(['firstname' => 'Student' . $i]);
            $generator->enrol_user($student->id, $course->id, 'student');
            $generator->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
            if ($i === 0) {
                $generator->create_group_member(['groupid' => $group->id, 'userid' => $teacher->id]);
            }
            $this->seed($assignment, 0, (int)$group->id);
        }
        $this->setUser($teacher);
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('flatarchive-assign-group-' . random_string());
        $result = assign_exporter::render($modinfo->get_cm($assignment->cmid), $modinfo, $directory);
        $this->assertCount(2, $result['filenames']);
        $html = file_get_contents($directory . '/' . $result['filename']);
        $this->assertStringContainsString('Student0', $html);
        $this->assertStringNotContainsString('Student1', $html);
        $report = file_get_contents($directory . '/' . $result['filenames'][1]);
        $this->assertStringContainsString('Response 1', $report);
        $this->assertStringContainsString(get_string('assigngroupsubmission', 'local_flatarchive'), $report);
    }
}
