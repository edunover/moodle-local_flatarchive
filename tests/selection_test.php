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

use local_flatarchive\archive\selection;

/**
 * Export selection cannot bypass module permissions or course boundaries.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\selection
 */
final class selection_test extends \advanced_testcase {
    /**
     * Reject a forged ID from another course even for an administrator.
     */
    public function test_other_course_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $other->id]);
        $this->expectException(\moodle_exception::class);
        selection::validate(get_fast_modinfo($course), [$page->cmid]);
    }

    /**
     * A teacher with a prohibited module capability cannot export its contents.
     */
    public function test_prohibited_module_view_is_rejected(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('mod/page:view', CAP_PROHIBIT, $role, \context_module::instance($page->cmid)->id);
        $this->setUser($teacher);
        $modinfo = get_fast_modinfo($course);
        $this->assertFalse(selection::permitted($modinfo->get_cm($page->cmid)));
        $this->expectException(\moodle_exception::class);
        selection::validate($modinfo, [$page->cmid]);
    }

    /**
     * Hidden modules remain unavailable to a student even with course archive permission.
     */
    public function test_hidden_module_is_rejected(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'visible' => 0]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $this->expectException(\moodle_exception::class);
        selection::validate(get_fast_modinfo($course), [$page->cmid]);
    }

    /**
     * Teachers can export hidden content they can access; unsupported types remain excluded.
     */
    public function test_authorised_hidden_module_and_supported_types(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'visible' => 0]);
        $modinfo = get_fast_modinfo($course);
        selection::validate($modinfo, [$page->cmid]);
        $this->assertTrue(selection::permitted($modinfo->get_cm($page->cmid)));
        $this->assertFalse(selection::supported('scorm'));
        $this->assertFalse(selection::supported('../page'));
    }
}
