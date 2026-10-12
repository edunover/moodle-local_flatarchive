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

namespace local_flatarchive\archive;

/**
 * Render native Moodle tags as safe offline labels for already authorised content.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tags {
    /**
     * Render one enabled tag area; callers must check item visibility first.
     *
     * @param string $component Tag component.
     * @param string $itemtype Tagged table.
     * @param int $itemid Authorised item ID.
     * @return string Static labels, or an empty string.
     */
    public static function render(string $component, string $itemtype, int $itemid): string {
        global $CFG;
        if (empty($CFG->usetags) || !\core_tag_tag::is_enabled($component, $itemtype)) {
            return '';
        }
        $labels = [];
        foreach (\core_tag_tag::get_item_tags($component, $itemtype, $itemid) as $tag) {
            $labels[] = \html_writer::span(s($tag->get_display_name(false)), 'fa-tag');
        }
        if (!$labels) {
            return '';
        }
        $key = $itemtype === 'course_modules' ? 'archiveactivitytags' : 'archivetags';
        if ($itemtype === 'course') {
            $key = 'archivecoursetags';
        }
        return \html_writer::div(
            \html_writer::span(get_string($key, 'local_flatarchive') . ': ', 'fa-tags-label') . implode(' ', $labels),
            'fa-tags'
        );
    }
}
