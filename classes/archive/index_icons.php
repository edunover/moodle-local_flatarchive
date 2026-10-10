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
 * Include offline activity icons in the archived course index.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class index_icons {
    /**
     * Decorate each exported list item and register its local icon in the ZIP.
     *
     * Only the opening list item is changed; inline media and nested lists remain intact.
     *
     * @param array $items Rendered list items keyed by course module ID.
     * @param \course_modinfo $modinfo Course module information.
     * @param string $directory Archive workspace directory.
     * @return array Decorated items and ZIP paths.
     */
    public static function add(array $items, \course_modinfo $modinfo, string $directory): array {
        global $CFG;

        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        $files = [];
        foreach ($items as $cmid => $item) {
            $cm = $modinfo->get_cm($cmid);
            $path = \local_flatarchive_copy_mod_icon($cm->modname, $directory . '/shared');
            if ($path === '') {
                continue;
            }
            if (!is_file($directory . '/' . $path)) {
                throw new \moodle_exception('archivefailed', 'local_flatarchive');
            }
            $icon = \html_writer::empty_tag('img', [
                'class' => 'fa-modicon',
                'src' => $path,
                'alt' => (string)$cm->get_module_type_name(),
                'width' => 24,
                'height' => 24,
            ]);
            $items[$cmid] = preg_replace_callback('/^<li\b[^>]*>/', static function ($match) use ($icon) {
                return $match[0] . $icon . ' ';
            }, $item, 1);
            $files[$path] = $directory . '/' . $path;
        }
        return ['items' => $items, 'files' => $files];
    }
}
