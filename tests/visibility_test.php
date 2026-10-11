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

/**
 * Distinguish hidden sections, hidden modules and available stealth modules.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::local_flatarchive_visibility_badges
 * @covers ::local_flatarchive_group_index_links
 * @covers ::local_flatarchive_render_index_template
 */
final class visibility_test extends \advanced_testcase {
    /**
     * The index shows a section badge only on its heading and retains activity-specific states.
     */
    public function test_hidden_and_stealth_states(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/index_template.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allowstealth', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'topics', 'numsections' => 2]);
        $normal = $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $hidden = $generator->create_module('page', ['course' => $course->id, 'section' => 2, 'visible' => 0]);
        $stealth = $generator->create_module('page', ['course' => $course->id, 'section' => 2]);
        $direct = $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $DB->set_field('course_sections', 'visible', 0, ['course' => $course->id, 'section' => 2]);
        $DB->set_field('course_modules', 'visibleoncoursepage', 0, ['id' => $direct->cmid]);
        rebuild_course_cache($course->id, true);
        $info = get_fast_modinfo($course);
        $items = [];
        foreach ([$normal, $hidden, $stealth, $direct] as $module) {
            $badges = local_flatarchive_visibility_badges($info->get_cm($module->cmid), $info);
            $items[$module->cmid] = '<li>' . $badges . '</li>';
        }
        $this->assertSame('<li></li>', $items[$normal->cmid]);
        $this->assertStringContainsString('fa-vis-hidden', $items[$hidden->cmid]);
        $this->assertStringNotContainsString('fa-vis-stealth', $items[$hidden->cmid]);
        $this->assertStringContainsString('fa-vis-stealth', $items[$stealth->cmid]);
        $this->assertStringContainsString('fa-vis-stealth', $items[$direct->cmid]);
        $sections = local_flatarchive_group_index_links($course, $info, $items);
        $html = local_flatarchive_render_index_template($course, $course->id, $sections, $course->shortname);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//h2/span[contains(@class,"fa-vis-section")]')->length);
        $this->assertSame(0, $xpath->query('//li//span[contains(@class,"fa-vis-section")]')->length);
        $this->assertSame(2, $xpath->query('//li//span[contains(@class,"fa-vis-stealth")]')->length);
        $this->assertSame(1, $xpath->query('//li//span[contains(@class,"fa-vis-hidden")]')->length);
    }
}
