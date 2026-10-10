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

use local_flatarchive\archive\choice_exporter;

/**
 * Choice content, publication rules and group boundaries.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\choice_exporter
 */
final class choice_exporter_test extends \advanced_testcase {
    /**
     * Create a clean fixture.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Read the generated page.
     *
     * @param \stdClass $choice Activity fixture.
     * @return string Generated HTML.
     */
    private function export(\stdClass $choice): string {
        $info = get_fast_modinfo($choice->course);
        $directory = make_request_directory();
        $export = choice_exporter::render($info->get_cm($choice->cmid), $info, $directory);
        return file_get_contents($directory . '/' . $export['filename']);
    }

    /**
     * Store a response without triggering events or completion changes.
     *
     * @param \stdClass $choice Activity fixture.
     * @param int $userid Respondent.
     * @param int $position Option position.
     */
    private function answer(\stdClass $choice, int $userid, int $position = 0): void {
        global $DB;
        $options = array_values($DB->get_records('choice_options', ['choiceid' => $choice->id], 'id'));
        $DB->insert_record('choice_answers', ['choiceid' => $choice->id, 'userid' => $userid,
            'optionid' => $options[$position]->id, 'timemodified' => 1700000000]);
    }

    /**
     * A report exporter can see named responses, multiple choices and local intro media.
     */
    public function test_report_and_media(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $choice = $generator->create_module('choice', ['course' => $course->id, 'allowmultiple' => 1,
            'option' => ['Été & hiver', 'Autre'], 'intro' => '<img src="@@PLUGINFILE@@/image.png" alt="Test">']);
        $student = $generator->create_user(['firstname' => 'ChoicePerson', 'lastname' => 'Respondent']);
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->answer($choice, $student->id);
        $this->answer($choice, $student->id, 1);
        $context = \context_module::instance($choice->cmid);
        get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'mod_choice',
            'filearea' => 'intro', 'itemid' => 0, 'filepath' => '/', 'filename' => 'image.png'], 'image bytes');
        $info = get_fast_modinfo($course);
        $directory = make_request_directory();
        $export = choice_exporter::render($info->get_cm($choice->cmid), $info, $directory);
        $html = file_get_contents($directory . '/' . $export['filename']);
        $this->assertStringContainsString('ChoicePerson Respondent', $html);
        $this->assertStringContainsString('Été &amp; hiver', $html);
        $this->assertStringContainsString(get_string('choicemultiple', 'local_flatarchive'), $html);
        $this->assertSame(2, substr_count($html, get_string('choicecount', 'local_flatarchive', 1)));
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertSame('image bytes', file_get_contents($directory . '/files/' . $choice->cmid . '/intro/0/image.png'));
    }

    /**
     * Anonymous published totals omit identities; closed results remain unavailable.
     */
    public function test_publication_and_anonymity(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $choice = $generator->create_module('choice', ['course' => $course->id, 'showresults' => 3, 'publish' => 0]);
        $student = $generator->create_user(['firstname' => 'SecretChoiceName']);
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->answer($choice, $student->id);
        $this->setUser($student);
        $html = $this->export($choice);
        $this->assertStringNotContainsString('SecretChoiceName', $html);
        $this->assertStringContainsString(get_string('choicecount', 'local_flatarchive', 1), $html);
        $DB->set_field('choice', 'showresults', 0, ['id' => $choice->id]);
        $html = $this->export($choice);
        $this->assertStringContainsString(get_string('choicerestricted', 'local_flatarchive'), $html);
        $this->assertStringNotContainsString(get_string('choicecount', 'local_flatarchive', 1), $html);
        $DB->set_field('choice', 'showresults', 2, ['id' => $choice->id]);
        $DB->set_field('choice', 'timeclose', time() + 3600, ['id' => $choice->id]);
        $this->assertStringContainsString(get_string('choicerestricted', 'local_flatarchive'), $this->export($choice));
        $DB->set_field('choice', 'timeclose', time() - 3600, ['id' => $choice->id]);
        $this->assertStringContainsString(get_string('choicecount', 'local_flatarchive', 1), $this->export($choice));
        $DB->set_field('choice', 'publish', 1, ['id' => $choice->id]);
        $this->assertStringContainsString('SecretChoiceName', $this->export($choice));
    }

    /**
     * Private reports require permission to download and cannot bypass view access.
     */
    public function test_report_download_and_view_permissions(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $choice = $generator->create_module('choice', ['course' => $course->id, 'showresults' => 0]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        $context = \context_module::instance($choice->cmid);
        assign_capability('mod/choice:downloadresponses', CAP_PROHIBIT, $role, $context->id);
        $this->setUser($teacher);
        $this->assertStringContainsString(get_string('choicerestricted', 'local_flatarchive'), $this->export($choice));
        assign_capability('mod/choice:view', CAP_PROHIBIT, $role, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->expectException(\required_capability_exception::class);
        $this->export($choice);
    }

    /**
     * Separate groups do not leak another group's response to a teacher.
     */
    public function test_separate_groups(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['groupmode' => SEPARATEGROUPS]);
        $choice = $generator->create_module('choice', ['course' => $course->id, 'groupmode' => SEPARATEGROUPS]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'teacher'], MUST_EXIST);
        $context = \context_module::instance($choice->cmid);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $role, $context->id);
        foreach (['OwnGroupStudent', 'OtherGroupStudent'] as $index => $name) {
            $group = $generator->create_group(['courseid' => $course->id]);
            $student = $generator->create_user(['firstname' => $name]);
            $generator->enrol_user($student->id, $course->id, 'student');
            $generator->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
            if ($index === 0) {
                $generator->create_group_member(['groupid' => $group->id, 'userid' => $teacher->id]);
            }
            $this->answer($choice, $student->id);
        }
        $this->setUser($teacher);
        $html = $this->export($choice);
        $this->assertStringContainsString('OwnGroupStudent', $html);
        $this->assertStringNotContainsString('OtherGroupStudent', $html);
    }
}
