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

namespace local_flatarchive\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Course resource selection through the external API.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\external\get_tree
 */
final class get_tree_test extends \externallib_advanced_testcase {
    /**
     * Set up independent Moodle fixtures for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Exercise the return-value validation performed by Moodle's AJAX server.
     *
     * @param int $courseid Course ID.
     * @return array Validated response.
     */
    private function read_tree(int $courseid): array {
        return \core_external\external_api::clean_returnvalue(get_tree::execute_returns(), get_tree::execute($courseid));
    }

    /**
     * Pages and Books are selectable; unsupported resources and other courses are excluded.
     */
    public function test_supported_modules_and_course_isolation(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'topics', 'numsections' => 2]);
        $other = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id, 'section' => 1, 'name' => 'Page A']);
        $book = $generator->create_module('book', ['course' => $course->id, 'section' => 1, 'name' => 'Book B']);
        $generator->create_module('url', ['course' => $course->id, 'section' => 1]);
        $generator->create_module('page', ['course' => $other->id]);

        $result = $this->read_tree($course->id);
        $this->assertCount(1, $result['sections']);
        $modules = $result['sections'][0]['modules'];
        $this->assertEquals([$page->cmid, $book->cmid], array_column($modules, 'cmid'));
        $this->assertSame(['page', 'book'], array_column($modules, 'type'));
        $this->assertSame(['Page A', 'Book B'], array_column($modules, 'name'));
    }

    /**
     * A course containing only a hidden text and media area remains archivable by a teacher.
     */
    public function test_text_and_media_only_course(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $label = $generator->create_module('label', ['course' => $course->id, 'visible' => 0]);
        $other = $generator->create_course();
        $generator->create_module('label', ['course' => $other->id]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);
        $sections = $this->read_tree($course->id)['sections'];
        $this->assertCount(1, $sections);
        $this->assertCount(1, $sections[0]['modules']);
        $module = $sections[0]['modules'][0];
        $this->assertEquals($label->cmid, $module['cmid']);
        $this->assertSame('label', $module['type']);
        $this->assertFalse((bool)$module['visible']);
    }

    /**
     * Hidden files are selectable in their own course by an authorised teacher.
     */
    public function test_file_only_course(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $file = $generator->create_module('resource', ['course' => $course->id, 'visible' => 0]);
        $other = $generator->create_course();
        $generator->create_module('resource', ['course' => $other->id]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);
        $sections = $this->read_tree($course->id)['sections'];
        $this->assertCount(1, $sections);
        $this->assertCount(1, $sections[0]['modules']);
        $module = $sections[0]['modules'][0];
        $this->assertEquals($file->cmid, $module['cmid']);
        $this->assertSame('resource', $module['type']);
        $this->assertFalse((bool)$module['visible']);
    }

    /**
     * Hidden folders are selectable in their own course by an authorised teacher.
     */
    public function test_folder_only_course(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $file = $generator->create_module('folder', ['course' => $course->id, 'visible' => 0]);
        $other = $generator->create_course();
        $generator->create_module('folder', ['course' => $other->id]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);
        $sections = $this->read_tree($course->id)['sections'];
        $this->assertCount(1, $sections);
        $this->assertCount(1, $sections[0]['modules']);
        $module = $sections[0]['modules'][0];
        $this->assertEquals($file->cmid, $module['cmid']);
        $this->assertSame('folder', $module['type']);
        $this->assertFalse((bool)$module['visible']);
    }

    /**
     * Course section order is preserved even when activities are created out of order.
     */
    public function test_section_order_and_default_names(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'topics', 'numsections' => 3]);
        $later = $generator->create_module('book', ['course' => $course->id, 'section' => 2]);
        $earlier = $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $sections = $this->read_tree($course->id)['sections'];

        $this->assertCount(2, $sections);
        $this->assertEquals($earlier->cmid, $sections[0]['modules'][0]['cmid']);
        $this->assertEquals($later->cmid, $sections[1]['modules'][0]['cmid']);
        $this->assertSame(get_section_name($course, 1), $sections[0]['name']);
        $this->assertNotEmpty($sections[0]['name']);
        $this->assertSame(get_section_name($course, 2), $sections[1]['name']);
    }

    /**
     * Hidden resources and sections remain selectable by an authorised teacher.
     */
    public function test_hidden_resources_for_teacher(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'topics', 'numsections' => 2]);
        $page = $generator->create_module('page', ['course' => $course->id, 'section' => 1, 'visible' => 0]);
        $book = $generator->create_module('book', ['course' => $course->id, 'section' => 2]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 2], '*', MUST_EXIST);
        course_update_section($course, $section, ['name' => 'Hidden section', 'visible' => 0]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);

        $sections = $this->read_tree($course->id)['sections'];
        $this->assertCount(2, $sections);
        $this->assertEquals($page->cmid, $sections[0]['modules'][0]['cmid']);
        $this->assertFalse((bool)$sections[0]['modules'][0]['visible']);
        $this->assertEquals($book->cmid, $sections[1]['modules'][0]['cmid']);
        $this->assertSame('Hidden section', $sections[1]['name']);
        $this->assertFalse((bool)$sections[1]['visible']);
    }

    /**
     * Courses without supported resources return an empty tree.
     */
    public function test_empty_and_unsupported_only_courses(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $this->assertSame(['sections' => []], $this->read_tree($course->id));
        $generator->create_module('url', ['course' => $course->id]);
        $this->assertSame(['sections' => []], $this->read_tree($course->id));
    }

    /**
     * Enrolment alone must not grant archive access.
     */
    public function test_student_without_archive_capability_is_rejected(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        get_tree::execute($course->id);
    }

    /**
     * A teacher's archive capability in one course must not apply to a second course.
     */
    public function test_capability_is_scoped_to_requested_course(): void {
        $generator = $this->getDataGenerator();
        $first = $generator->create_course();
        $second = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $first->id, 'editingteacher');
        $generator->enrol_user($user->id, $second->id, 'student');
        $this->setUser($user);
        $this->assertSame(['sections' => []], $this->read_tree($first->id));
        $this->expectException(\required_capability_exception::class);
        get_tree::execute($second->id);
    }

    /**
     * Invalid IDs must fail instead of returning a misleading empty course.
     */
    public function test_missing_course_is_rejected(): void {
        $this->expectException(\dml_missing_record_exception::class);
        get_tree::execute(-1);
    }
}
