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
 * Export Feedback questionnaires and permitted submitted responses as static pages.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class feedback_exporter {
    /** @var string[] Supported core question types. */
    private const TYPES = ['label', 'pagebreak', 'multichoice', 'multichoicerated', 'numeric',
        'textfield', 'textarea', 'info', 'captcha'];

    /**
     * Write the questionnaire and permitted response pages without changing Moodle data.
     *
     * @param \cm_info $cm Feedback module.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Archive directory.
     * @return array Generated filenames and course index item.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/feedback/lib.php');
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'feedback') {
            throw new \coding_exception('The feedback exporter requires a feedback activity');
        }
        $context = \context_module::instance($cm->id);
        require_capability('mod/feedback:view', $context);
        if (!$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $feedback = $DB->get_record('feedback', ['id' => $cm->instance], '*', MUST_EXIST);
        $items = $DB->get_records('feedback_item', ['feedback' => $feedback->id], 'position, id');
        $filename = 'feedback-' . $cm->id . '.html';
        $filenames = [$filename];
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        $content = self::text($feedback->intro, $feedback->introformat, $context, 'intro', 0, $directory, $cm->id);
        $content .= \html_writer::tag('p', get_string('feedbackscope', 'local_flatarchive'));
        $content .= \html_writer::tag('h2', get_string('feedbackquestions', 'local_flatarchive'));
        foreach ($items as $item) {
            if ($item->typ === 'pagebreak') {
                $content .= \html_writer::tag('hr', '');
                continue;
            }
            $body = self::question($item, $context, $directory, (int)$cm->id);
            if ($item->dependitem && isset($items[$item->dependitem])) {
                $condition = (object)['question' => $items[$item->dependitem]->name, 'value' => $item->dependvalue];
                $body .= \html_writer::tag('p', s(get_string('feedbackcondition', 'local_flatarchive', $condition)));
            }
            $content .= \html_writer::tag('section', $body, ['id' => 'question-' . $item->id]);
        }
        if (!$items) {
            $content .= \html_writer::tag('p', get_string('feedbackempty', 'local_flatarchive'));
        }
        if ($feedback->page_after_submit !== '') {
            $content .= \html_writer::tag('h2', get_string('feedbackaftersubmit', 'local_flatarchive'));
            $content .= self::text(
                $feedback->page_after_submit,
                $feedback->page_after_submitformat,
                $context,
                'page_after_submit',
                0,
                $directory,
                $cm->id
            );
        }
        $content .= reports::anchor() . \html_writer::tag('h2', get_string('feedbackresponses', 'local_flatarchive'));
        if (!has_capability('mod/feedback:viewreports', $context)) {
            $content .= \html_writer::tag('p', get_string('feedbackresponsesrestricted', 'local_flatarchive'));
        } else {
            // Completed responses only, scoped to this activity and course (including local course_id = 0).
            $responses = $DB->get_records_select(
                'feedback_completed',
                'feedback = :feedback AND (courseid = 0 OR courseid = :courseid)',
                ['feedback' => $feedback->id, 'courseid' => $cm->course],
                'id'
            );
            // Avoid exposing submission order through anonymous page numbers. Do not shuffle Moodle records.
            $responses = array_values($responses);
            shuffle($responses);
            $links = '';
            $number = 0;
            foreach ($responses as $response) {
                if (!groups_user_groups_visible($cm->course, $response->userid, $cm)) {
                    continue;
                }
                $anonymous = (int)$response->anonymous_response !== FEEDBACK_ANONYMOUS_NO;
                $user = null;
                if (!$anonymous) {
                    $fields = 'id, deleted, ' . implode(', ', \core_user\fields::get_name_fields());
                    $user = $DB->get_record('user', ['id' => $response->userid, 'deleted' => 0], $fields);
                    if (!$user) {
                        continue;
                    }
                }
                $number++;
                $title = get_string('feedbackresponse', 'local_flatarchive', $number);
                $title .= ' — ' . ($anonymous ? get_string('anonymous', 'feedback') : fullname($user));
                $responsefile = 'feedback-' . $cm->id . '-response-' . $number . '.html';
                $body = \html_writer::tag('p', \html_writer::link(
                    $filename,
                    get_string('backtofeedback', 'local_flatarchive')
                ));
                if (!$anonymous) {
                    $body .= \html_writer::tag('time', s(userdate($response->timemodified)), [
                        'datetime' => gmdate('Y-m-d\\TH:i:s\\Z', $response->timemodified),
                    ]);
                }
                $values = $DB->get_records('feedback_value', ['completed' => $response->id], '', 'item, value, course_id');
                foreach ($items as $item) {
                    $value = $values[$item->id] ?? null;
                    if (!$value || (int)$value->course_id !== (int)$response->courseid) {
                        continue;
                    }
                    // Automatic metadata can identify an anonymous submission; never include it in that case.
                    if (!$item->hasvalue || ($anonymous && $item->typ === 'info')) {
                        continue;
                    }
                    $body .= \html_writer::tag('h2', s($item->name));
                    $body .= self::answer($item, (string)$value->value);
                }
                $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $title, $body, '', $badges);
                \local_flatarchive_write_file($directory . '/' . $responsefile, $html);
                $filenames[] = $responsefile;
                $links .= \html_writer::tag('li', \html_writer::link($responsefile, s($title)));
            }
            $content .= $links === '' ? \html_writer::tag('p', get_string('feedbacknoresponses', 'local_flatarchive')) :
                \html_writer::tag('ul', $links);
        }
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $content, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        return ['filename' => $filename, 'filenames' => $filenames,
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name)) . $badges)];
    }

    /**
     * Render a static question, preserving choices, bounds and required status.
     *
     * @param \stdClass $item Question record.
     * @param \context_module $context Module context.
     * @param string $directory Archive directory.
     * @param int $cmid Module ID.
     * @return string Static question HTML.
     */
    private static function question(\stdClass $item, \context_module $context, string $directory, int $cmid): string {
        if ($item->typ === 'label') {
            return self::text($item->presentation, FORMAT_HTML, $context, 'item', $item->id, $directory, $cmid);
        }
        $html = \html_writer::tag('h3', s($item->name));
        if (!in_array($item->typ, self::TYPES, true)) {
            return $html . \html_writer::tag('p', get_string('feedbackunsupported', 'local_flatarchive'));
        }
        $html .= \html_writer::tag('p', s(get_string($item->typ, 'feedback')));
        if ($item->required) {
            $html .= \html_writer::tag('p', get_string('required'));
        }
        if (in_array($item->typ, ['multichoice', 'multichoicerated'], true)) {
            $choices = self::choices($item);
            $html .= \html_writer::alist(array_map('s', $choices));
        } else if ($item->typ === 'numeric') {
            $html .= feedback_get_item_class('numeric')->get_display_name_postfix($item);
        } else if ($item->typ === 'info') {
            $keys = [1 => 'responsetime', 2 => 'course', 3 => 'coursecategory'];
            $key = $keys[(int)$item->presentation] ?? 'info';
            $component = in_array($key, ['course', 'coursecategory']) ? 'moodle' : 'feedback';
            $html .= \html_writer::tag('p', s(get_string($key, $component)));
        }
        return $html;
    }

    /**
     * Decode core choice definitions, retaining weights for rated choices.
     *
     * @param \stdClass $item Question record.
     * @return array One-based choice labels.
     */
    private static function choices(\stdClass $item): array {
        $info = feedback_get_item_class($item->typ)->get_info($item);
        $rated = $item->typ === 'multichoicerated';
        $separator = $rated ? FEEDBACK_MULTICHOICERATED_LINE_SEP : FEEDBACK_MULTICHOICE_LINE_SEP;
        $choices = [];
        foreach (explode($separator, $info->presentation) as $index => $choice) {
            if ($rated) {
                $parts = explode(FEEDBACK_MULTICHOICERATED_VALUE_SEP, $choice, 2);
                $choice = isset($parts[1]) ? $parts[1] . ' (' . $parts[0] . ')' : $choice;
            }
            $choices[$index + 1] = $choice;
        }
        return $choices;
    }

    /**
     * Decode submitted values without enabling filters, scripts or interactive controls.
     *
     * @param \stdClass $item Question record.
     * @param string $value Stored answer.
     * @return string Escaped answer HTML.
     */
    private static function answer(\stdClass $item, string $value): string {
        if (!in_array($item->typ, self::TYPES, true)) {
            return \html_writer::tag('p', get_string('feedbackunsupported', 'local_flatarchive'));
        }
        if (in_array($item->typ, ['multichoice', 'multichoicerated'], true)) {
            $choices = self::choices($item);
            $selected = [];
            $separator = $item->typ === 'multichoice' ? FEEDBACK_MULTICHOICE_LINE_SEP : FEEDBACK_MULTICHOICERATED_LINE_SEP;
            foreach (explode($separator, $value) as $index) {
                if (isset($choices[(int)$index])) {
                    $selected[] = $choices[(int)$index];
                }
            }
            return \html_writer::alist(array_map('s', $selected));
        }
        if ($item->typ === 'info' && (int)$item->presentation === 1 && is_numeric($value)) {
            $value = userdate((int)$value);
        }
        return \html_writer::tag('p', nl2br(s($value)));
    }

    /**
     * Copy embedded files and format content without live filters.
     *
     * @param string $text Source content.
     * @param int $format Moodle text format.
     * @param \context_module $context Module context.
     * @param string $area File area.
     * @param int $itemid File item ID.
     * @param string $directory Archive directory.
     * @param int $cmid Module ID.
     * @return string Offline HTML.
     */
    private static function text(
        string $text,
        int $format,
        \context_module $context,
        string $area,
        int $itemid,
        string $directory,
        int $cmid
    ): string {
        $text = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $text,
            $context,
            'mod_feedback',
            $area,
            $itemid,
            $directory,
            $cmid
        );
        return format_text($text, $format, ['context' => $context, 'filter' => false]);
    }
}
