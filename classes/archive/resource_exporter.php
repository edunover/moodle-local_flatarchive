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
 * Preserve file resources and link their main file from the course index.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class resource_exporter {
    /**
     * Copy the complete content area, retaining relative paths and original bytes.
     *
     * Moodle identifies the main file by descending sortorder, then ascending ID.
     * Companion files are retained so a bundled HTML file can find its assets.
     *
     * @param \cm_info $cm File course module.
     * @param \course_modinfo $modinfo Course structure used for visibility badges.
     * @param string $directory Archive workspace directory.
     * @return string Rendered list item linking to the main file.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): string {
        global $CFG;

        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        if ($cm->modname !== 'resource') {
            throw new \coding_exception('The resource exporter requires a file resource');
        }
        $context = \context_module::instance($cm->id);
        $files = get_file_storage()->get_area_files(
            $context->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false
        );
        if (!$files) {
            throw new \moodle_exception('resourcefilenotfound', 'local_flatarchive', '', $cm->name);
        }
        $mainfile = reset($files);
        \local_flatarchive_copy_pluginfiles_and_rewrite(
            '',
            $context,
            'mod_resource',
            'content',
            0,
            $directory,
            (int)$cm->id
        );
        $path = paths::assets((int)$cm->id, 'content', 0) . $mainfile->get_filepath() . $mainfile->get_filename();
        $url = implode('/', array_map('rawurlencode', explode('/', $path)));
        $link = \html_writer::link($url, s($cm->name));
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        return \html_writer::tag('li', $link . ($badges ? ' ' . $badges : ''), ['id' => 'module-' . $cm->id]);
    }
}
