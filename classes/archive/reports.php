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
     * @param array $options Selected course sections (activity reports remain with selected activities)
     * @return array Generated ZIP files and course index link
     */
    public static function write(
        \course_modinfo $modinfo,
        array $exports,
        string $directory,
        array $options = ['participants', 'grades', 'reports']
    ): array {
        global $CFG;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        $course = $modinfo->get_course();
        require_capability('local/flatarchive:archive', \context_course::instance($course->id));
        $snapshot = time();
        $result = course_reports::write($modinfo, $directory, $snapshot, $options);
        $body = \html_writer::tag('p', s(get_string('reportsscope', 'local_flatarchive')));
        $body .= \html_writer::tag('p', s(get_string('reportssnapshot', 'local_flatarchive', userdate($snapshot))));
        $body .= \html_writer::tag('h2', get_string('reportscourse', 'local_flatarchive'));
        $body .= \html_writer::alist($result['entries']);

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
                $result['files'] += self::activity($cm, $modinfo, $filename, $directory);
            }
        }
        if (!in_array('reports', $options, true)) {
            return ['files' => $result['files'], 'link' => $result['navigation']];
        }
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

    /**
     * Move an exporter's existing report section to a dedicated local activity report page.
     * @param \cm_info $cm Activity
     * @param \course_modinfo $modinfo Course
     * @param string $main Registered main activity document
     * @param string $directory Workspace
     * @return array Generated files
     */
    private static function activity(\cm_info $cm, \course_modinfo $modinfo, string $main, string $directory): array {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">' . file_get_contents($directory . '/' . $main), LIBXML_NONET);
            $xpath = new \DOMXPath($dom);
            $marker = $xpath->query('//div[@class="card-body"]/div[@id="fa-activity-reports"]')->item(0);
            if (!$marker) {
                return [];
            }
            $report = '';
            for ($node = $marker; $node !== null; $node = $next) {
                $next = $node->nextSibling;
                $report .= $dom->saveHTML($node);
                $node->parentNode->removeChild($node);
            }
            $filename = $cm->modname . '-' . $cm->id . '-reports.html';
            $title = $cm->name . ' — ' . get_string(self::ACTIVITIES[$cm->modname], 'local_flatarchive');
            $back = \html_writer::tag('p', \html_writer::link($main, get_string('settingsbackactivity', 'local_flatarchive')));
            $html = \local_flatarchive_render_page_template($modinfo->get_course(), null, $title, $back . $report);
            \local_flatarchive_write_file($directory . '/' . $filename, $html);

            // Settings is injected before this step. Reuse its paragraph as a single navigation row.
            $heading = $xpath->query('//main//h1')->item(0);
            $settings = $xpath->query('//main//p[a[contains(@href, "-settings.html")]]')->item(0);
            $nav = $dom->createElement('nav');
            $nav->setAttribute('class', 'fa-course-tools');
            $nav->setAttribute('aria-label', get_string('activitynavigation', 'local_flatarchive'));
            if ($settings) {
                while ($settings->firstChild) {
                    $nav->appendChild($settings->firstChild);
                }
                $settings->parentNode->replaceChild($nav, $settings);
            } else {
                $heading->parentNode->insertBefore($nav, $heading->nextSibling);
            }
            $link = $dom->createElement('a');
            $link->setAttribute('href', $filename);
            $link->setAttribute('class', 'fa-activity-reports-link');
            $link->appendChild($dom->createTextNode(get_string('archivereports', 'local_flatarchive')));
            $nav->appendChild($link);
            foreach (iterator_to_array($dom->childNodes) as $node) {
                if ($node->nodeType === XML_PI_NODE) {
                    $dom->removeChild($node);
                }
            }
            \local_flatarchive_write_file($directory . '/' . $main, $dom->saveHTML());
            return [$filename => $directory . '/' . $filename];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
