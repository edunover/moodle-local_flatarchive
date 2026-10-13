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
 * Explicit return links in each generated document template.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::local_flatarchive_render_page_template
 * @covers ::local_flatarchive_render_book_toc_template
 */
final class index_navigation_test extends \advanced_testcase {
    /**
     * Root documents and nested chapters return to the same course index.
     */
    public function test_all_document_templates(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        foreach (['page', 'book_toc'] as $template) {
            require_once($CFG->dirroot . '/local/flatarchive/archive_templates/' . $template . '_template.php');
        }
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $cases = [
            [local_flatarchive_render_page_template($course, null, 'Report', 'Content'), 'index.html'],
            [local_flatarchive_render_page_template($course, null, 'Chapter', 'Content', '../'), '../index.html'],
            [local_flatarchive_render_book_toc_template($course, 'Book', []), '../index.html'],
        ];
        foreach ($cases as [$html, $href]) {
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $links = (new \DOMXPath($dom))->query('//a[@class="fa-back-index"]');
            $this->assertSame(1, $links->length);
            $this->assertSame($href, $links->item(0)->getAttribute('href'));
            $this->assertSame(get_string('backtocourseindex', 'local_flatarchive'), $links->item(0)->textContent);
        }
    }
}
