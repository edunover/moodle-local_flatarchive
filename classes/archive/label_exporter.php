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
 * Export text and media areas inline in the course index.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class label_exporter {
    /**
     * Copy introduction attachments and render a list item for the root index.
     *
     * Filters are disabled because their output may depend on Moodle JavaScript
     * or live services. Moodle still formats and cleans the stored content.
     * External URLs remain external; only files stored in this area are copied.
     *
     * @param \cm_info $cm Text and media course module.
     * @param \course_modinfo $modinfo Course structure used for visibility badges.
     * @param string $directory Archive workspace directory.
     * @return string Formatted HTML list item.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): string {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        if ($cm->modname !== 'label') {
            throw new \coding_exception('The label exporter requires a text and media area');
        }
        $label = $DB->get_record('label', ['id' => $cm->instance], 'id, intro, introformat', MUST_EXIST);
        if (!selection::permitted($cm)) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $context = \context_module::instance($cm->id);
        $content = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $label->intro,
            $context,
            'mod_label',
            'intro',
            0,
            $directory,
            (int)$cm->id
        );
        $content = format_text($content, $label->introformat, ['context' => $context, 'filter' => false]);
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        return \html_writer::tag('li', $badges . $content, ['class' => 'fa-textmedia', 'id' => 'module-' . $cm->id]);
    }
}
