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

use local_flatarchive\archive\tags;

/**
 * Offline tags across modules, courses and native content tag areas.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\tags
 */
final class tags_test extends \advanced_testcase {
    /**
     * Module tags are preserved for all supported activities without adding online links.
     */
    public function test_all_module_tags_and_disabled_tagging(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('usetags', 1);
        set_config('keeptagnamecase', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $types = ['page', 'book', 'label', 'resource', 'folder', 'glossary', 'wiki', 'feedback', 'choice', 'forum'];
        $items = [];
        foreach ($types as $type) {
            $module = $generator->create_module($type, ['course' => $course->id]);
            \core_tag_tag::set_item_tags(
                'core',
                'course_modules',
                $module->cmid,
                \context_module::instance($module->cmid),
                ['Été & ' . $type]
            );
            $items[$module->cmid] = '<li><a href="local.html">Title</a><ul><li>Nested</li></ul></li>';
        }
        $result = tags::index_items($items);
        $this->assertCount(10, $result);
        foreach ($result as $html) {
            $this->assertStringContainsString('Été &amp;', $html);
            $this->assertStringContainsString('<ul><li>Nested</li></ul>', $html);
            $this->assertSame(1, substr_count($html, 'class="fa-tags"'));
            $this->assertSame(1, substr_count($html, '<a '));
            $this->assertStringEndsWith('</div></li>', $html);
        }
        set_config('usetags', 0);
        $this->assertSame($items, tags::index_items($items));
    }

    /**
     * Course, module and book table of contents templates expose their own tags.
     */
    public function test_course_and_activity_templates(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/index_template.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/book_toc_template.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('usetags', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $book = $generator->create_module('book', ['course' => $course->id]);
        \core_tag_tag::set_item_tags('core', 'course', $course->id, \context_course::instance($course->id), ['coursetag']);
        \core_tag_tag::set_item_tags(
            'core',
            'course_modules',
            $book->cmid,
            \context_module::instance($book->cmid),
            ['moduletag']
        );
        $index = local_flatarchive_render_index_template($course, $course->id, [], $course->shortname);
        $this->assertStringContainsString('coursetag', $index);
        $this->assertStringNotContainsString('moduletag', $index);
        $toc = local_flatarchive_render_book_toc_template($course, 'Book', [], '../', '', $book->cmid);
        $this->assertStringContainsString('moduletag', $toc);
        $cm = get_fast_modinfo($course)->get_cm($book->cmid);
        $page = local_flatarchive_render_page_template($course, $cm, 'Test', '<p>Content</p>');
        $this->assertStringContainsString('moduletag', $page);
        $this->assertStringContainsString('<p>Content</p>', $page);
    }

    /**
     * Content tag areas remain distinct even when their numeric IDs match.
     */
    public function test_content_area_isolation_and_empty_tags(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('usetags', 1);
        $areas = ['book' => 'book_chapters', 'glossary' => 'glossary_entries',
            'wiki' => 'wiki_pages', 'forum' => 'forum_posts'];
        foreach ($areas as $module => $area) {
            \core_tag_tag::set_item_tags('mod_' . $module, $area, 123, \context_system::instance(), [$module . '-only']);
        }
        foreach ($areas as $module => $area) {
            $html = tags::render('mod_' . $module, $area, 123);
            $this->assertStringContainsString($module . '-only', $html);
            foreach (array_keys($areas) as $other) {
                if ($other !== $module) {
                    $this->assertStringNotContainsString($other . '-only', $html);
                }
            }
            $this->assertSame('', tags::render('mod_' . $module, $area, 124));
        }
    }
}
