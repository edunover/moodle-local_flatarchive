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
 * Read-only course reports with native capabilities and a documented participant scope.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_reports {
    /** @var array Report labels and required capabilities. */
    private const TYPES = [
        'participants' => ['archiveparticipants', ['moodle/course:viewparticipants']],
        'grades' => ['reportsgrades', ['gradereport/grader:view', 'moodle/grade:viewall']],
        'completion' => ['reportscoursecompletion', ['report/completion:view']],
        'progress' => ['reportsprogress', ['report/progress:view']],
        'participation' => ['reportsparticipation', ['report/participation:view']],
    ];

    /**
     * Write available snapshots and describe unavailable reports without reading their data.
     * @param \course_modinfo $modinfo Course information
     * @param string $directory Workspace
     * @param int $snapshot Export timestamp
     * @param array $options Selected course sections
     * @return array Files and rendered index entries
     */
    public static function write(
        \course_modinfo $modinfo,
        string $directory,
        int $snapshot,
        array $options = ['participants', 'grades', 'reports']
    ): array {
        global $CFG;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        require_once($CFG->libdir . '/completionlib.php');
        $course = $modinfo->get_course();
        $context = \context_course::instance($course->id);
        require_capability('local/flatarchive:archive', $context);
        $group = self::group($course);
        $files = [];
        $entries = [];
        $navigation = [];
        foreach (self::TYPES as $type => [$key, $capabilities]) {
            $option = in_array($type, ['participants', 'grades'], true) ? $type : 'reports';
            if (!in_array($option, $options, true)) {
                continue;
            }
            $title = get_string($key, 'local_flatarchive');
            $reason = '';
            foreach ($capabilities as $capability) {
                if (!has_capability($capability, $context)) {
                    $reason = 'reportsdenied';
                }
            }
            if ($group === null) {
                $reason = 'reportsgroupdenied';
            }
            if ($reason) {
                if (!in_array($type, ['participants', 'grades'], true)) {
                    $entries[] = s($title . ' — ' . get_string($reason, 'local_flatarchive'));
                }
                continue;
            }
            $body = self::$type($modinfo, $group, $snapshot);
            $filename = 'report-' . $type . '.html';
            $scope = get_string('reportsallgroups', 'local_flatarchive');
            if ($group) {
                $scope = get_string('reportsgroup', 'local_flatarchive', groups_get_group_name($group));
            }
            $intro = in_array($type, ['participants', 'grades'], true) ? '' :
                \html_writer::tag('p', \html_writer::link('reports.html', get_string('backtoreports', 'local_flatarchive')));
            $intro .= \html_writer::tag('p', s(get_string('reportssnapshot', 'local_flatarchive', userdate($snapshot))));
            $intro .= \html_writer::tag('p', s($scope));
            $intro .= \html_writer::tag('p', get_string(
                $type === 'participants' ? 'archiveparticipantsscope' : 'reportsparticipantscope',
                'local_flatarchive'
            ));
            $html = \local_flatarchive_render_page_template($course, null, $title, $intro . $body);
            \local_flatarchive_write_file($directory . '/' . $filename, $html);
            $files[$filename] = $directory . '/' . $filename;
            if (in_array($type, ['participants', 'grades'], true)) {
                $key = $type === 'grades' ? 'archivegradebook' : 'archiveparticipants';
                $navigation[] = \html_writer::link($filename, get_string($key, 'local_flatarchive'));
            } else {
                $entries[] = \html_writer::link($filename, s($title));
            }
        }
        return ['files' => $files, 'entries' => $entries, 'navigation' => implode('', $navigation)];
    }

    /**
     * Apply the course report group boundary, including teachers without any allowed group.
     * @param \stdClass $course Course
     * @return int|null Selected group, zero for all, null when forbidden
     */
    private static function group(\stdClass $course): ?int {
        global $USER;
        $group = (int)groups_get_course_group($course, true);
        if (
            groups_get_course_groupmode($course) == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups', \context_course::instance($course->id))
        ) {
            if (!$group || !groups_is_member($group, $USER->id)) {
                return null;
            }
        }
        return $group;
    }

    /**
     * List enrolled participants with their course-effective roles and enrolment status.
     * @param \course_modinfo $modinfo Course
     * @param int $group Selected group
     * @param int $snapshot Export timestamp
     * @return string Escaped participant table
     */
    private static function participants(\course_modinfo $modinfo, int $group, int $snapshot): string {
        $context = \context_course::instance($modinfo->get_course_id());
        $fields = 'u.id, u.deleted, ' . implode(', ', \core_user\fields::get_name_fields());
        $users = get_enrolled_users($context, '', $group, $fields, 'u.lastname, u.firstname, u.id');
        $rows = [];
        foreach ($users as $user) {
            if ($user->deleted) {
                continue;
            }
            $roles = [];
            foreach (get_user_roles($context, $user->id, true) as $assignment) {
                // The assignment ID is not the role ID expected by role_get_name().
                $role = (object)['id' => $assignment->roleid, 'name' => $assignment->name,
                    'shortname' => $assignment->shortname];
                $roles[$role->id] = html_entity_decode(strip_tags(role_get_name($role, $context)), ENT_QUOTES, 'UTF-8');
            }
            $active = is_enrolled($context, $user, '', true);
            $rows[] = [fullname($user, has_capability('moodle/site:viewfullnames', $context)),
                $roles ? implode(', ', $roles) : '—',
                get_string($active ? 'archiveparticipantactive' : 'archiveparticipantinactive', 'local_flatarchive')];
        }
        return self::table([get_string('fullname'), get_string('roles'),
            get_string('archiveparticipantstatus', 'local_flatarchive')], $rows);
    }

    /**
     * Select active enrolled users, using completion tracking or configured gradebook roles.
     * @param \stdClass $course Course
     * @param int $group Selected group
     * @param bool $completion Whether to select completion-tracked users
     * @return array Participants
     */
    private static function users(\stdClass $course, int $group, bool $completion = false): array {
        global $CFG;
        $context = \context_course::instance($course->id);
        $capability = $completion ? 'moodle/course:isincompletionreports' : '';
        $fields = 'u.id, u.deleted, ' . implode(', ', \core_user\fields::get_name_fields());
        $users = get_enrolled_users($context, $capability, $group, $fields, 'u.lastname, u.firstname, u.id', 0, 0, true);
        $roles = array_map('intval', explode(',', $CFG->gradebookroles ?? ''));
        foreach ($users as $id => $user) {
            $graded = $completion;
            if (!$completion) {
                foreach (get_user_roles($context, $id, true) as $role) {
                    if (in_array((int)$role->roleid, $roles, true)) {
                        $graded = true;
                        break;
                    }
                }
            }
            if ($user->deleted || !$graded) {
                unset($users[$id]);
            }
        }
        return $users;
    }

    /**
     * Preserve stored grades without regrading or exposing hidden totals indirectly.
     * @param \course_modinfo $modinfo Course
     * @param int $group Group
     * @param int $snapshot Timestamp
     * @return string Report body
     */
    private static function grades(\course_modinfo $modinfo, int $group, int $snapshot): string {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');
        $course = $modinfo->get_course();
        $context = \context_course::instance($course->id);
        $hidden = has_capability('moodle/grade:viewhidden', $context);
        $items = [];
        foreach ($DB->get_records('grade_items', ['courseid' => $course->id], 'sortorder, id') as $record) {
            $item = new \grade_item($record, false);
            if (
                $item->gradetype == GRADE_TYPE_NONE || (!$hidden && ($item->is_hidden() ||
                    in_array($item->itemtype, ['course', 'category'], true)))
            ) {
                continue;
            }
            if ($item->itemtype === 'mod') {
                $cm = $modinfo->instances[$item->itemmodule][$item->iteminstance] ?? null;
                if (!$cm || !$cm->uservisible) {
                    continue;
                }
            }
            $items[] = $item;
        }
        $head = [get_string('fullname')];
        foreach ($items as $item) {
            $name = html_entity_decode(strip_tags($item->get_name()), ENT_QUOTES, 'UTF-8');
            $head[] = $name . ($item->is_hidden() ? ' (' . get_string('hidden', 'local_flatarchive') . ')' : '');
        }
        $rows = [];
        foreach (self::users($course, $group) as $user) {
            $row = [fullname($user, has_capability('moodle/site:viewfullnames', $context))];
            foreach ($items as $item) {
                $grade = $item->get_grade($user->id, false);
                $value = $grade->finalgrade;
                if (!$hidden && $grade->is_hidden()) {
                    $row[] = get_string('hidden', 'local_flatarchive');
                } else {
                    $row[] = $value === null ? '—' : strip_tags(grade_format_gradevalue($value, $item, true));
                }
            }
            $rows[] = $row;
        }
        $body = \html_writer::tag('p', get_string('reportsgradesscope', 'local_flatarchive'));
        if (!$hidden) {
            $body .= \html_writer::tag('p', get_string('reportshiddentotals', 'local_flatarchive'));
        }
        return $body . self::table($head, $rows);
    }

    /**
     * Preserve recorded course completion and criteria dates without invoking aggregation.
     * @param \course_modinfo $modinfo Course
     * @param int $group Group
     * @param int $snapshot Timestamp
     * @return string Report body
     */
    private static function completion(\course_modinfo $modinfo, int $group, int $snapshot): string {
        global $DB;
        $course = $modinfo->get_course();
        $info = new \completion_info($course);
        if (!$info->is_enabled() || !$info->has_criteria()) {
            return \html_writer::tag('p', get_string('reportscompletiondisabled', 'local_flatarchive'));
        }
        $criteria = $info->get_criteria();
        $head = [get_string('fullname'), get_string('reportscoursecompletion', 'local_flatarchive')];
        foreach ($criteria as $criterion) {
            $head[] = html_entity_decode(strip_tags($criterion->get_title_detailed()), ENT_QUOTES, 'UTF-8');
        }
        $rows = [];
        foreach (self::users($course, $group, true) as $user) {
            $record = $DB->get_record('course_completions', ['course' => $course->id, 'userid' => $user->id]);
            $row = [fullname($user), self::completed((int)($record->timecompleted ?? 0))];
            foreach ($criteria as $criterion) {
                $completion = $info->get_user_completion($user->id, $criterion);
                $row[] = self::completed((int)$completion->timecompleted);
            }
            $rows[] = $row;
        }
        return self::table($head, $rows);
    }

    /**
     * Preserve all visible activity completion states, including non-exported activities.
     * @param \course_modinfo $modinfo Course
     * @param int $group Group
     * @param int $snapshot Timestamp
     * @return string Report body
     */
    private static function progress(\course_modinfo $modinfo, int $group, int $snapshot): string {
        $course = $modinfo->get_course();
        $info = new \completion_info($course);
        if (!$info->is_enabled()) {
            return \html_writer::tag('p', get_string('reportscompletiondisabled', 'local_flatarchive'));
        }
        $activities = array_filter($info->get_activities(), static fn($cm) => $cm->uservisible);
        $head = [get_string('fullname')];
        foreach ($activities as $cm) {
            $head[] = $cm->name;
        }
        $states = [0 => 'reportsincomplete', 1 => 'reportscomplete', 2 => 'reportspassed', 3 => 'reportsfailed'];
        $rows = [];
        foreach (self::users($course, $group, true) as $user) {
            $row = [fullname($user)];
            foreach ($activities as $cm) {
                $data = $info->get_data($cm, true, $user->id);
                $state = $states[(int)$data->completionstate] ?? 'reportsincomplete';
                $row[] = get_string($state, 'local_flatarchive') .
                    ($data->timemodified ? ' — ' . userdate($data->timemodified) : '');
            }
            $rows[] = $row;
        }
        return self::table($head, $rows);
    }

    /**
     * Count retained participant events through the native report's preferred SQL log reader.
     * @param \course_modinfo $modinfo Course
     * @param int $group Group
     * @param int $snapshot Timestamp
     * @return string Report body
     */
    private static function participation(\course_modinfo $modinfo, int $group, int $snapshot): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/report/participation/locallib.php');
        $logtable = report_participation_get_log_table_name();
        $body = \html_writer::tag('p', get_string('reportsparticipationscope', 'local_flatarchive'));
        if (!$logtable || !preg_match('/\A[a-z][a-z0-9_]*\z/', $logtable)) {
            return $body . \html_writer::tag('p', get_string('reportsnologs', 'local_flatarchive'));
        }
        $course = $modinfo->get_course();
        $context = \context_course::instance($course->id);
        $users = self::users($course, $group);
        $rows = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || !groups_group_visible($group, $course, $cm)) {
                continue;
            }
            $params = ['course' => $course->id, 'cmid' => $cm->id, 'level' => CONTEXT_MODULE,
                'edu' => \core\event\base::LEVEL_PARTICIPATING, 'snapshot' => $snapshot];
            $anonymous = has_capability('moodle/site:viewanonymousevents', $context) ? '' : ' AND anonymous = 0';
            $counts = $DB->get_records_sql("SELECT userid,
                    COUNT(DISTINCT CASE WHEN crud = 'r' THEN timecreated END) AS views,
                    COUNT(DISTINCT CASE WHEN crud IN ('c', 'u', 'd') THEN timecreated END) AS posts
                FROM {" . $logtable . "}
                WHERE courseid = :course AND contextinstanceid = :cmid AND contextlevel = :level
                    AND edulevel = :edu AND timecreated <= :snapshot AND origin IN ('web', 'ws')" . $anonymous . "
                GROUP BY userid", $params);
            foreach ($users as $user) {
                if (!groups_user_groups_visible($course->id, $user->id, $cm)) {
                    continue;
                }
                $count = $counts[$user->id] ?? null;
                $rows[] = [fullname($user), $cm->name, (string)($count->views ?? 0), (string)($count->posts ?? 0)];
            }
        }
        return $body . self::table([get_string('fullname'), get_string('activity'),
            get_string('reportsviews', 'local_flatarchive'), get_string('reportsposts', 'local_flatarchive')], $rows);
    }

    /**
     * Describe a stored completion timestamp.
     * @param int $time Timestamp or zero
     * @return string Plain text status
     */
    private static function completed(int $time): string {
        return $time ? get_string('reportscomplete', 'local_flatarchive') . ' — ' . userdate($time) :
            get_string('reportsincomplete', 'local_flatarchive');
    }

    /**
     * Render escaped cells with accessible headers and horizontal overflow on small screens.
     * @param array $head Plain text column names
     * @param array $rows Plain text row values
     * @return string Safe table or empty-state message
     */
    private static function table(array $head, array $rows): string {
        if (!$rows || count($head) < 2) {
            return \html_writer::tag('p', get_string('reportsempty', 'local_flatarchive'));
        }
        $html = '';
        foreach ($head as $cell) {
            $html .= \html_writer::tag('th', s($cell), ['scope' => 'col']);
        }
        $html = \html_writer::tag('thead', \html_writer::tag('tr', $html));
        $body = '';
        foreach ($rows as $row) {
            $cells = '';
            foreach ($row as $index => $cell) {
                $cells .= \html_writer::tag($index ? 'td' : 'th', s($cell), $index ? [] : ['scope' => 'row']);
            }
            $body .= \html_writer::tag('tr', $cells);
        }
        $html .= \html_writer::tag('tbody', $body);
        return \html_writer::div(\html_writer::tag('table', $html, ['class' => 'fa-settings-table']), 'fa-report-table');
    }
}
