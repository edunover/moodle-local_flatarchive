<?php
// This file is part of Moodle - https://moodle.org/
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

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
require_once($CFG->dirroot . '/local/flatarchive/archive_templates/index_template.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Regression tests for the archived course hierarchy.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::local_flatarchive_group_index_links
 * @covers ::local_flatarchive_render_index_template
 */
final class index_structure_test extends \advanced_testcase {
    /**
     * Selection order must not change course order or merge identical section names.
     */
    public function test_groups_selected_resources_in_course_order(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'topics', 'numsections' => 3]);
        $general = $generator->create_module('page', ['course' => $course->id, 'section' => 0]);
        $first = $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $book = $generator->create_module('book', ['course' => $course->id, 'section' => 1]);
        $later = $generator->create_module('page', ['course' => $course->id, 'section' => 2]);
        $generator->create_module('page', ['course' => $course->id, 'section' => 3]);
        $modinfo = get_fast_modinfo($course);
        foreach ([1, 2] as $number) {
            course_update_section($course, $modinfo->get_section_info($number), ['name' => 'Same section']);
        }
        $sections = local_flatarchive_group_index_links($course, get_fast_modinfo($course), [
            $later->cmid => '<li>Later</li>',
            $book->cmid => '<li>Book</li>',
            $first->cmid => '<li>First</li>',
            $general->cmid => '<li>General</li>',
        ]);
        $this->assertCount(3, $sections);
        $this->assertSame(get_section_name($course, 0), $sections[0]['name']);
        $this->assertSame(['<li>General</li>'], $sections[0]['links']);
        $this->assertSame(['<li>First</li>', '<li>Book</li>'], $sections[1]['links']);
        $this->assertSame(['<li>Later</li>'], $sections[2]['links']);
        $this->assertSame($sections[1]['name'], $sections[2]['name']);
        $this->assertNotEquals($sections[1]['id'], $sections[2]['id']);
        $this->assertSame([], local_flatarchive_group_index_links($course, get_fast_modinfo($course), []));
    }

    /**
     * Section headings are escaped and relative resource links retain their hierarchy.
     */
    public function test_index_contains_safe_section_headings_and_resource_links(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $html = local_flatarchive_render_index_template($course, $course->id, [
            ['id' => 7, 'name' => '<script>alert(1)</script> & Section',
                'links' => ['<li><a href="test%20ppt--book-83/index.html">test ppt</a></li>']],
            ['id' => 8, 'name' => 'Next section',
                'links' => ['<li><a href="page--page-1.html">Page</a></li>']],
        ], $course->shortname);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//section')->length);
        $this->assertSame(0, $xpath->query('//script')->length);
        $this->assertSame('<script>alert(1)</script> & Section', $xpath->query('//h2[@id="section-7"]')->item(0)->textContent);
        $this->assertSame(
            'test%20ppt--book-83/index.html',
            $xpath->query('//section[@aria-labelledby="section-7"]/ul/li/a')->item(0)->getAttribute('href')
        );
        $this->assertSame('Page', $xpath->query('//section[@aria-labelledby="section-8"]/ul/li/a')->item(0)->textContent);
    }
}
