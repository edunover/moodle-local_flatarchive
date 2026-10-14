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
 * Export a Choice activity and the results the exporter may access.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class choice_exporter {
    /**
     * Write one static page without recording a view or modifying answers.
     *
     * @param \cm_info $cm Choice module.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Archive directory.
     * @return array Generated filenames and course index entry.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/choice/lib.php');
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'choice') {
            throw new \coding_exception('The choice exporter requires a choice activity');
        }
        $context = \context_module::instance($cm->id);
        require_capability('mod/choice:view', $context);
        if (!$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $choice = $DB->get_record('choice', ['id' => $cm->instance], '*', MUST_EXIST);
        if (
            $choice->timeopen > time() && !$choice->showpreview
            && !has_capability('moodle/course:manageactivities', $context)
        ) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $options = $DB->get_records('choice_options', ['choiceid' => $choice->id], 'id');
        $intro = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $choice->intro,
            $context,
            'mod_choice',
            'intro',
            0,
            $directory,
            (int)$cm->id
        );
        $content = format_text($intro, $choice->introformat, ['context' => $context, 'filter' => false]);
        $mode = $choice->allowmultiple ? 'choicemultiple' : 'choicesingle';
        $content .= \html_writer::tag('p', get_string($mode, 'local_flatarchive'));
        $content .= \html_writer::tag('h2', get_string('choiceoptions', 'local_flatarchive'));
        $labels = [];
        foreach ($options as $option) {
            $label = s($option->text);
            if ($choice->limitanswers) {
                $label .= ' — ' . get_string('choicelimit', 'local_flatarchive', (int)$option->maxanswers);
            }
            $labels[] = $label;
        }
        $content .= \html_writer::alist($labels);
        $content .= reports::anchor() . \html_writer::tag('h2', get_string('choiceresults', 'local_flatarchive'));
        // Private report exports require both report access and the native download capability.
        $report = has_capability('mod/choice:readresponses', $context)
            && has_capability('mod/choice:downloadresponses', $context);
        if (!$report && !choice_can_view_results($choice)) {
            $content .= \html_writer::tag('p', get_string('choicerestricted', 'local_flatarchive'));
        } else {
            $named = $report || (int)$choice->publish === (int)CHOICE_PUBLISH_NAMES;
            $groupmode = groups_get_activity_groupmode($cm);
            $responses = choice_get_response_data($choice, $cm, $groupmode, !$choice->includeinactive);
            $content .= \html_writer::tag('p', get_string('choicescope', 'local_flatarchive'));
            $groupid = $groupmode ? groups_get_activity_group($cm) : 0;
            if ($groupid) {
                $content .= \html_writer::tag('p', s(groups_get_group_name($groupid)));
            }
            foreach ($options as $option) {
                $users = [];
                foreach ($responses[$option->id] ?? [] as $user) {
                    if (groups_user_groups_visible($cm->course, $user->id, $cm)) {
                        $users[] = $user;
                    }
                }
                $content .= \html_writer::tag('h3', s($option->text));
                $content .= \html_writer::tag('p', get_string('choicecount', 'local_flatarchive', count($users)));
                if ($named && $users) {
                    $names = [];
                    foreach ($users as $user) {
                        $names[] = s(fullname($user, has_capability('moodle/site:viewfullnames', $context)));
                    }
                    $content .= \html_writer::alist($names);
                }
            }
        }
        $filename = 'choice-' . $cm->id . '.html';
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $content, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        return ['filename' => $filename, 'filenames' => [$filename],
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name)) . $badges)];
    }
}
