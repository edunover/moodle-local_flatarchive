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
 * Native Moodle H5P activities as static content and authorised historical reports.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class h5p_exporter {
    /**
     * Export a native H5P activity without executing its libraries or recording a view.
     *
     * @param \cm_info $cm Activity.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Archive directory.
     * @return array Main page, generated pages and index entry.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'h5pactivity') {
            throw new \coding_exception('The H5P exporter requires a native H5P activity');
        }
        $context = \context_module::instance($cm->id);
        require_capability('mod/h5pactivity:view', $context);
        // The package contains the solutions, even when the player conceals them from learners.
        require_capability('moodle/course:manageactivities', $context);
        if (!$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $activity = $DB->get_record('h5pactivity', ['id' => $cm->instance], '*', MUST_EXIST);
        $files = get_file_storage()->get_area_files($context->id, 'mod_h5pactivity', 'package', 0, 'id', false);
        if (!$files) {
            throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
        }
        $package = new h5p_package(reset($files), $directory, $cm->id);
        $renderer = new h5p_renderer($package);
        $filename = 'h5pactivity-' . $cm->id . '.html';
        $filenames = [];
        $intro = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $activity->intro,
            $context,
            'mod_h5pactivity',
            'intro',
            0,
            $directory,
            (int)$cm->id
        );
        $body = format_text($intro, $activity->introformat, ['context' => $context, 'filter' => false]);
        $body .= $renderer->notice('h5pstaticnotice');
        $body .= \html_writer::tag('p', \html_writer::link($package->source, get_string('h5psource', 'local_flatarchive')));
        $library = $package->metadata['mainLibrary'] ?? '';
        $version = '';
        foreach ($package->metadata['preloadedDependencies'] ?? [] as $dependency) {
            if (($dependency['machineName'] ?? '') === $library) {
                $version = ' ' . (int)$dependency['majorVersion'] . '.' . (int)$dependency['minorVersion'];
            }
        }
        $params = $package->content;
        $parts = [];
        $slideshow = $library === 'H5P.CoursePresentation';
        if ($slideshow) {
            $parts = $params['presentation']['slides'] ?? [];
            $body .= $renderer->media($params['presentation']['globalBackgroundSelector']['imageGlobalBackground'] ?? [], 'image');
        } else if ($library === 'H5P.QuestionSet') {
            $parts = $params['questions'] ?? [];
            $body .= $renderer->rich($params['introPage']['title'] ?? '');
            $body .= $renderer->rich($params['introPage']['introduction'] ?? '');
            $body .= $renderer->media($params['introPage']['backgroundImage'] ?? [], 'image');
            $body .= $renderer->feedback($params['endGame'] ?? []);
        }
        if ($parts && ($version === '' || (int)trim($version) === 1)) {
            $links = [];
            $titles = [];
            foreach ($parts as $i => $part) {
                $links[$i + 1] = 'h5pactivity-' . $cm->id . '-part-' . ($i + 1) . '.html';
                $titles[$i + 1] = get_string($slideshow ? 'h5pslide' : 'h5pquestion', 'local_flatarchive', $i + 1);
                $title = $slideshow ? ($part['keywords'][0]['main'] ?? '') : ($part['metadata']['title'] ?? '');
                if ($title !== '') {
                    $titles[$i + 1] .= ' — ' . $title;
                }
            }
            $items = [];
            foreach ($parts as $i => $part) {
                $number = $i + 1;
                $navigation = \html_writer::link($filename, get_string('h5pback', 'local_flatarchive'));
                $slidelinks = $links;
                foreach (['previous' => $number - 1, 'next' => $number + 1] as $direction => $target) {
                    if (isset($links[$target])) {
                        $navigation .= ' · ' . \html_writer::link($links[$target], s($titles[$target]));
                        $slidelinks[$direction] = $links[$target];
                    }
                }
                $content = \html_writer::tag('p', $navigation);
                $content .= $slideshow ? $renderer->slide($part, 0, $slidelinks) : $renderer->child($part);
                self::write($cm, $modinfo, $directory, $links[$number], $titles[$number], $content);
                $filenames[] = $links[$number];
                $items[] = \html_writer::link($links[$number], s($titles[$number]));
            }
            $body .= \html_writer::alist($items, [], 'ol');
        } else {
            $types = ['H5P.DragText', 'H5P.DragQuestion', 'H5P.QuestionSet', 'H5P.CoursePresentation', 'H5P.InteractiveVideo'];
            $body .= in_array($library, $types, true) ? $renderer->render($library . $version, $params)
                : $renderer->notice('h5punsupported', $library);
        }
        $body .= $renderer->credits($package->metadata);
        $body .= self::configuration($params);
        $body .= self::attempts($cm, $modinfo, $directory, $filename, $filenames);
        self::write($cm, $modinfo, $directory, $filename, $cm->name, $body);
        $filenames[] = $filename;
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        return ['filename' => $filename, 'filenames' => $filenames,
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name)) . $badges)];
    }

    /**
     * Record authoring settings that affect the archived exercise.
     *
     * @param array $params Content parameters.
     * @return string Documentary HTML.
     */
    private static function configuration(array $params): string {
        $rows = '';
        foreach (['passPercentage', 'poolSize', 'randomQuestions', 'disableBackwardsNavigation', 'behaviour', 'override'] as $key) {
            if (isset($params[$key])) {
                $value = json_encode($params[$key], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $rows .= \html_writer::tag('tr', \html_writer::tag('th', s($key)) .
                    \html_writer::tag('td', \html_writer::tag('pre', s($value))));
            }
        }
        if (!$rows) {
            return '';
        }
        return \html_writer::tag('h2', get_string('h5pcontentsettings', 'local_flatarchive')) .
            \html_writer::tag('table', $rows, ['class' => 'fa-settings-table']);
    }

    /**
     * Export only reports approved by the native manager, including its group checks.
     *
     * @param \cm_info $cm Activity.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Archive directory.
     * @param string $main Main page.
     * @param array $filenames Generated report pages.
     * @return string Links to historical reports.
     */
    private static function attempts(
        \cm_info $cm,
        \course_modinfo $modinfo,
        string $directory,
        string $main,
        array &$filenames
    ): string {
        global $DB, $PAGE;
        $manager = \mod_h5pactivity\local\manager::create_from_coursemodule($cm);
        $html = \html_writer::tag('h2', get_string('h5pattempts', 'local_flatarchive'));
        $html .= \html_writer::tag('p', get_string('h5pattemptnotice', 'local_flatarchive'));
        if (!$manager->can_view_all_attempts()) {
            return $html . \html_writer::tag('p', get_string('h5pnoreports', 'local_flatarchive'));
        }
        $records = $DB->get_recordset('h5pactivity_attempts', ['h5pactivityid' => $cm->instance], 'userid, attempt, id');
        $links = [];
        try {
            foreach ($records as $record) {
                $report = $manager->get_report(null, (int)$record->id);
                if (!$report || !$report->get_user() || $report->get_user()->deleted) {
                    continue;
                }
                $title = fullname($report->get_user()) . ' — ' . get_string('h5pattempt', 'local_flatarchive', $record->attempt);
                $content = \html_writer::link($main, get_string('h5pback', 'local_flatarchive'));
                $content .= \html_writer::tag('p', s(userdate($record->timecreated)));
                $content .= self::score($record);
                $results = $DB->get_records('h5pactivity_attempts_results', ['attemptid' => $record->id], 'timecreated, id');
                foreach ($results as $result) {
                    $content .= \html_writer::tag('h3', s(strip_tags($result->description)));
                    $content .= self::score($result);
                    $output = \mod_h5pactivity\output\result::create_from_record($result);
                    $data = $output ? $output->export_for_template($PAGE->get_renderer('core')) : null;
                    if ($data && !empty($data->options)) {
                        $rows = '';
                        foreach ($data->options as $option) {
                            $option = (object)$option;
                            $rows .= \html_writer::tag('tr', \html_writer::tag('td', s($option->description ?? '')) .
                                \html_writer::tag('td', s($option->useranswer->answer ?? '')) .
                                \html_writer::tag('td', s($option->correctanswer->answer ?? '')));
                        }
                        $head = \html_writer::tag('tr', \html_writer::tag('th', s($data->optionslabel)) .
                            \html_writer::tag('th', s($data->answerlabel)) . \html_writer::tag('th', s($data->correctlabel)));
                        $content .= \html_writer::tag('table', $head . $rows, ['class' => 'fa-settings-table']);
                    } else if ($result->response !== '') {
                        // Preserve unknown xAPI response encodings explicitly, never guess their meaning.
                        $content .= \html_writer::tag('p', get_string('h5prawresponse', 'local_flatarchive'));
                        $content .= \html_writer::tag('pre', s($result->response));
                    }
                }
                $filename = 'h5pactivity-' . $cm->id . '-attempt-' . $record->id . '.html';
                self::write($cm, $modinfo, $directory, $filename, $title, $content);
                $filenames[] = $filename;
                $links[] = \html_writer::link($filename, s($title));
            }
        } finally {
            $records->close();
        }
        $list = $links ? \html_writer::alist($links) : \html_writer::tag('p', get_string('h5pnoreports', 'local_flatarchive'));
        return $html . $list;
    }

    /**
     * Recorded score and status, with no recalculation.
     *
     * @param \stdClass $record Attempt or result.
     * @return string HTML.
     */
    private static function score(\stdClass $record): string {
        $html = \html_writer::tag('p', get_string('h5pscore', 'local_flatarchive') . ': ' .
            s((string)$record->rawscore . ' / ' . (string)$record->maxscore));
        foreach (['completion', 'success'] as $key) {
            $label = get_string('h5p' . $key, 'local_flatarchive');
            $value = $record->$key === null ? 'h5punknown' : ($record->$key ? 'h5pyes' : 'h5pno');
            $html .= \html_writer::tag('p', $label . ': ' . get_string($value, 'local_flatarchive'));
        }
        if ($record->duration !== null) {
            $html .= \html_writer::tag('p', get_string('h5pduration', 'local_flatarchive', (int)$record->duration));
        }
        return $html;
    }

    /**
     * Write a page using the common offline navigation and styling.
     *
     * @param \cm_info $cm Activity.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Destination.
     * @param string $filename Filename.
     * @param string $title Title.
     * @param string $body Body.
     */
    private static function write(
        \cm_info $cm,
        \course_modinfo $modinfo,
        string $directory,
        string $filename,
        string $title,
        string $body
    ): void {
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $title, $body);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
    }
}
