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
 * Portable index of course reports and the reports in exported activities.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class reports {
    /** @var array Activity report headings in existing export documents. */
    public const ACTIVITIES = [
        'quiz' => 'quizattempts', 'lesson' => 'lessonattempts', 'feedback' => 'feedbackresponses',
        'choice' => 'choiceresults', 'assign' => 'assignrecords', 'h5pactivity' => 'h5pattempts',
        'workshop' => 'wsassessments',
    ];

    /**
     * Add an anchor to an existing report section without creating dangling links in standalone exports.
     * @return string Anchor markup
     */
    public static function anchor(): string {
        return \html_writer::tag('div', '', ['id' => 'fa-activity-reports']);
    }

    /**
     * Generate the report index after authorised activity exports have completed.
     * @param \course_modinfo $modinfo Current course
     * @param array $exports Successful exporter results indexed by course module ID
     * @param string $directory Workspace
     * @return array Generated ZIP files and course index link
     */
    public static function write(\course_modinfo $modinfo, array $exports, string $directory): array {
        global $CFG;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        $course = $modinfo->get_course();
        require_capability('local/flatarchive:archive', \context_course::instance($course->id));
        $snapshot = time();
        $result = course_reports::write($modinfo, $directory, $snapshot);
        $body = \html_writer::tag('p', s(get_string('reportsscope', 'local_flatarchive')));
        $body .= \html_writer::tag('p', s(get_string('reportssnapshot', 'local_flatarchive', userdate($snapshot))));
        $body .= \html_writer::tag('h2', get_string('reportscourse', 'local_flatarchive'));
        $body .= \html_writer::alist($result['entries']);
        $body .= \html_writer::tag('h2', get_string('reportsactivities', 'local_flatarchive'));
        $entries = [];
        foreach ($modinfo->get_sections() as $ids) {
            foreach ($ids as $id) {
                $cm = $modinfo->get_cm($id);
                if (!isset($exports[$id], self::ACTIVITIES[$cm->modname]) || !selection::permitted($cm)) {
                    continue;
                }
                $export = $exports[$id];
                $filename = $export['filename'] ?? '';
                // Only a registered exporter document can be linked, never an attached or arbitrary file.
                if (
                    !in_array($filename, $export['filenames'] ?? [], true) || !paths::relative($filename) ||
                        !is_file($directory . '/' . $filename)
                ) {
                    continue;
                }
                $label = $cm->name . ' — ' . get_string(self::ACTIVITIES[$cm->modname], 'local_flatarchive');
                $entries[] = \html_writer::link($filename . '#fa-activity-reports', s($label));
            }
        }
        $body .= $entries ? \html_writer::alist($entries) :
            \html_writer::tag('p', get_string('reportsnoactivities', 'local_flatarchive'));
        $filename = 'reports.html';
        $html = \local_flatarchive_render_page_template(
            $course,
            null,
            get_string('archivereports', 'local_flatarchive'),
            $body
        );
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        $result['files'][$filename] = $directory . '/' . $filename;
        return ['files' => $result['files'], 'link' => $result['navigation'] . \html_writer::link(
            $filename,
            get_string('archivereports', 'local_flatarchive'),
            ['class' => 'fa-reports-link']
        )];
    }
}
