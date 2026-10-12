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
 * Preserve URL resources without requesting external content or personal parameters.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class url_exporter {
    /**
     * Write the resource document and its offline description.
     *
     * @param \cm_info $cm Course module
     * @param \course_modinfo $modinfo Course structure
     * @param string $directory Archive workspace
     * @return array Generated documents and index entry
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'url' || !$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        require_capability('mod/url:view', $cm->context);
        $instance = $DB->get_record('url', ['id' => $cm->instance], '*', MUST_EXIST);
        $intro = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $instance->intro,
            $cm->context,
            'mod_url',
            'intro',
            0,
            $directory,
            (int)$cm->id
        );
        $body = format_text($intro, $instance->introformat, ['context' => $cm->context, 'filter' => false]);
        $address = trim($instance->externalurl);
        // Only explicitly safe external protocols become clickable in the offline document.
        if (preg_match('~^(https?|ftp|mailto|tel):~i', $address)) {
            $body .= \html_writer::tag('p', \html_writer::link($address, s($address)));
        } else {
            $body .= \html_writer::tag('p', s($address));
        }
        $body .= \html_writer::tag('p', get_string('urlexternalnotice', 'local_flatarchive'));
        if (!empty($instance->parameters) && $instance->parameters !== 'a:0:{}') {
            $body .= \html_writer::tag('p', get_string('urlparametersnotice', 'local_flatarchive'));
        }
        $filename = 'url-' . $cm->id . '.html';
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $body, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        return [
            'filename' => $filename,
            'filenames' => [$filename],
            'item' => \html_writer::tag(
                'li',
                \html_writer::link($filename, s($cm->name)) . $badges,
                ['id' => 'module-' . $cm->id]
            ),
        ];
    }
}
