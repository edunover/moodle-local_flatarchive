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
 * Archive assignment instructions and teacher-accessible submission history.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assign_exporter {
    /**
     * Generate instructions and individual reports using Moodle's participant checks.
     *
     * @param \cm_info $cm Course module
     * @param \course_modinfo $modinfo Course structure
     * @param string $directory Archive workspace
     * @return array Generated documents and index entry
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'assign' || !$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        require_capability('mod/assign:view', $cm->context);
        $assignment = new \assign($cm->context, $cm, $modinfo->get_course());
        $instance = $assignment->get_instance();
        $body = self::text($instance->intro, (int)$instance->introformat, 'mod_assign', 'intro', 0, $cm, $directory);
        $body .= self::files('mod_assign', 'introattachment', 0, $cm, $directory);
        if (
            has_capability('moodle/course:manageactivities', $cm->context) ||
            has_capability('mod/assign:grade', $cm->context)
        ) {
            $body .= self::text(
                $instance->activity ?? '',
                (int)($instance->activityformat ?? FORMAT_HTML),
                'mod_assign',
                'activityattachment',
                0,
                $cm,
                $directory
            );
            $body .= self::files('mod_assign', 'activityattachment', 0, $cm, $directory);
        }
        $body .= \html_writer::tag('p', get_string('assignarchivescope', 'local_flatarchive'));
        $filename = 'assign-' . $cm->id . '.html';
        $filenames = [$filename];
        $links = '';
        if (has_capability('mod/assign:grade', $cm->context)) {
            $participants = $assignment->list_participants(0, false);
            if (
                groups_get_activity_groupmode($cm) == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups', $cm->context)
            ) {
                $allowed = [];
                foreach (groups_get_all_groups($cm->course, $USER->id, $cm->groupingid) as $group) {
                    $allowed += $assignment->list_participants($group->id, false);
                }
                $participants = array_intersect_key($participants, $allowed);
            }
            foreach ($participants as $participant) {
                if (!$assignment->can_view_submission($participant->id)) {
                    continue;
                }
                $anonymousid = $DB->get_field('assign_user_mapping', 'id', [
                    'assignment' => $instance->id, 'userid' => $participant->id,
                ]);
                $title = $assignment->is_blind_marking() ?
                    get_string('assignanonymous', 'local_flatarchive', $anonymousid ?: count($filenames)) :
                    fullname($participant);
                $report = self::report($assignment, $participant, $cm, $directory);
                if ($report === '') {
                    continue;
                }
                // Do not put real user IDs in filenames for anonymous marking.
                $reportfile = 'assign-' . $cm->id . '-participant-' . count($filenames) . '.html';
                $back = \html_writer::tag('p', \html_writer::link($filename, s($cm->name)));
                $html = \local_flatarchive_render_page_template($modinfo->get_course(), null, $title, $back . $report);
                \local_flatarchive_write_file($directory . '/' . $reportfile, $html);
                $filenames[] = $reportfile;
                $links .= \html_writer::tag('li', \html_writer::link($reportfile, s($title)));
            }
        }
        $body .= \html_writer::tag('h2', get_string('assignrecords', 'local_flatarchive'));
        $body .= $links ? \html_writer::tag('ul', $links) :
            \html_writer::tag('p', get_string('assignnorecords', 'local_flatarchive'));
        $plugingroups = [$assignment->get_submission_plugins(), $assignment->get_feedback_plugins()];
        foreach ($plugingroups as $index => $plugins) {
            $supported = $index === 0 ? ['file', 'onlinetext'] : ['file', 'comments'];
            foreach ($plugins as $plugin) {
                if ($plugin->is_enabled() && !in_array($plugin->get_type(), $supported, true)) {
                    $body .= \html_writer::tag('p', get_string('assignunsupported', 'local_flatarchive', s($plugin->get_name())));
                }
            }
        }
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $body, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        return [
            'filename' => $filename,
            'filenames' => $filenames,
            'item' => \html_writer::tag(
                'li',
                \html_writer::link($filename, s($cm->name)) . $badges,
                ['id' => 'module-' . $cm->id]
            ),
        ];
    }

    /**
     * Render stored attempts, online text, original files and individual grades.
     *
     * @param \assign $assignment Moodle assignment API
     * @param \stdClass $participant Authorized participant
     * @param \cm_info $cm Module
     * @param string $directory Workspace
     * @return string Report body, empty when there are no records
     */
    private static function report(\assign $assignment, \stdClass $participant, \cm_info $cm, string $directory): string {
        global $DB;
        $instance = $assignment->get_instance();
        $submissions = $DB->get_records('assign_submission', [
            'assignment' => $instance->id, 'userid' => $participant->id,
        ], 'attemptnumber');
        if ($instance->teamsubmission) {
            $group = $assignment->get_submission_group($participant->id);
            $submissions = $DB->get_records('assign_submission', [
                'assignment' => $instance->id, 'userid' => 0, 'groupid' => $group ? $group->id : 0,
            ], 'attemptnumber');
        }
        $attempts = [];
        foreach ($submissions as $submission) {
            $attempts[(int)$submission->attemptnumber]['submission'] = $submission;
        }
        $grades = $DB->get_records('assign_grades', [
            'assignment' => $instance->id, 'userid' => $participant->id,
        ], 'attemptnumber');
        foreach ($grades as $grade) {
            $attempts[(int)$grade->attemptnumber]['grade'] = $grade;
        }
        ksort($attempts);
        $body = '';
        foreach ($attempts as $number => $attempt) {
            $body .= \html_writer::tag('h2', get_string('assignattempt', 'local_flatarchive', $number + 1));
            if (isset($attempt['submission'])) {
                $submission = $attempt['submission'];
                $statuskey = 'submissionstatus_' . $submission->status;
                $status = get_string_manager()->string_exists($statuskey, 'assign') ?
                    get_string($statuskey, 'assign') : s($submission->status);
                $body .= \html_writer::tag('p', $status . ' — ' . userdate($submission->timemodified));
                if ($instance->teamsubmission) {
                    $body .= \html_writer::tag('p', get_string('assigngroupsubmission', 'local_flatarchive'));
                }
                $online = $DB->get_record('assignsubmission_onlinetext', ['submission' => $submission->id]);
                if ($online) {
                    $body .= self::text(
                        $online->onlinetext,
                        (int)$online->onlineformat,
                        'assignsubmission_onlinetext',
                        'submissions_onlinetext',
                        (int)$submission->id,
                        $cm,
                        $directory
                    );
                }
                $body .= self::files('assignsubmission_file', 'submission_files', (int)$submission->id, $cm, $directory);
            }
            if (isset($attempt['grade'])) {
                $grade = $attempt['grade'];
                $value = get_string('assignnotgraded', 'local_flatarchive');
                if ($grade->grade !== null && $grade->grade >= 0) {
                    $value = format_float($grade->grade, 2);
                    if ($instance->grade < 0) {
                        $scale = $DB->get_field('scale', 'scale', ['id' => -$instance->grade]);
                        $values = explode(',', (string)$scale);
                        $value = $values[(int)$grade->grade - 1] ?? $value;
                    } else {
                        $value .= ' / ' . format_float($instance->grade, 2);
                    }
                }
                $body .= \html_writer::tag('p', get_string('assigngrade', 'local_flatarchive') . ': ' . s($value));
                $feedback = $DB->get_record('assignfeedback_comments', ['grade' => $grade->id]);
                if ($feedback) {
                    $body .= self::text(
                        $feedback->commenttext ?? '',
                        (int)$feedback->commentformat,
                        'assignfeedback_comments',
                        'feedback',
                        (int)$grade->id,
                        $cm,
                        $directory
                    );
                }
                $body .= self::files('assignfeedback_file', 'feedback_files', (int)$grade->id, $cm, $directory);
            }
        }
        if ($body !== '' && $instance->markingworkflow) {
            $state = $DB->get_field('assign_user_flags', 'workflowstate', [
                'assignment' => $instance->id, 'userid' => $participant->id,
            ]);
            $statekey = 'markingworkflowstate' . ($state ?: 'notmarked');
            $statelabel = get_string_manager()->string_exists($statekey, 'assign') ?
                get_string($statekey, 'assign') : (string)$state;
            $body = \html_writer::tag('p', get_string('markingworkflowstate', 'assign') . ': ' . s($statelabel)) . $body;
        }
        return $body;
    }

    /**
     * Format text and copy its embedded media.
     *
     * @param string $text Stored text
     * @param int $format Moodle text format
     * @param string $component File component
     * @param string $area File area
     * @param int $id File item
     * @param \cm_info $cm Module
     * @param string $directory Workspace
     * @return string Offline HTML
     */
    private static function text(
        string $text,
        int $format,
        string $component,
        string $area,
        int $id,
        \cm_info $cm,
        string $directory
    ): string {
        $text = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $text,
            $cm->context,
            $component,
            $area,
            $id,
            $directory,
            (int)$cm->id
        );
        return format_text($text, $format, ['context' => $cm->context, 'filter' => false]);
    }

    /**
     * Copy original attachments and provide portable, encoded links.
     *
     * @param string $component File component
     * @param string $area File area
     * @param int $id File item
     * @param \cm_info $cm Module
     * @param string $directory Workspace
     * @return string Attachment list
     */
    private static function files(string $component, string $area, int $id, \cm_info $cm, string $directory): string {
        \local_flatarchive_copy_pluginfiles_and_rewrite('', $cm->context, $component, $area, $id, $directory, (int)$cm->id);
        $items = '';
        $files = get_file_storage()->get_area_files($cm->context->id, $component, $area, $id, 'filepath, filename', false);
        foreach ($files as $file) {
            $path = paths::assets((int)$cm->id, $area, $id) . $file->get_filepath() . $file->get_filename();
            $url = implode('/', array_map('rawurlencode', explode('/', $path)));
            $items .= \html_writer::tag('li', \html_writer::link($url, s($file->get_filepath() . $file->get_filename())));
        }
        return $items === '' ? '' : \html_writer::tag('ul', $items);
    }
}
