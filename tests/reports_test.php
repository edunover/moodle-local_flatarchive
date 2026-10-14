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

namespace local_flatarchive;

use local_flatarchive\archive\course_reports;
use local_flatarchive\archive\reports;
use local_flatarchive\archive\choice_exporter;
use local_flatarchive\archive\layout;

/**
 * Report snapshots, permission boundaries and portable index navigation.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\course_reports
 * @covers \local_flatarchive\archive\reports
 */
final class reports_test extends \advanced_testcase {
    /**
     * Initialise the isolated report fixture.
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/index_template.php');
        set_config('enablecompletion', 1);
    }

    /**
     * Create a recorded manual grade without invoking grade recalculation during export.
     * @param \stdClass $course Course
     * @param \stdClass $student Student
     * @param string $name Item label
     * @param float $value Stored value
     * @param int $hidden Hidden flag
     * @return \grade_item Item
     */
    private function grade(\stdClass $course, \stdClass $student, string $name, float $value, int $hidden = 0): \grade_item {
        global $DB;
        $item = new \grade_item(['courseid' => $course->id, 'itemtype' => 'manual', 'itemname' => $name,
            'gradetype' => GRADE_TYPE_VALUE, 'grademax' => 100, 'grademin' => 0, 'hidden' => $hidden], false);
        $item->insert();
        $DB->insert_record('grade_grades', ['itemid' => $item->id, 'userid' => $student->id,
            'finalgrade' => $value, 'rawgrademax' => 100, 'rawgrademin' => 0, 'hidden' => $hidden]);
        return $item;
    }

    /**
     * Course snapshots include stored grades/completions and do not alter their source records.
     */
    public function test_recorded_grades_and_completion_are_read_only(): void {
        global $DB;
        $g = $this->getDataGenerator();
        $course = $g->create_course(['enablecompletion' => 1]);
        $student = $g->create_user(['firstname' => 'ReportStudent', 'lastname' => 'Alpha']);
        $g->enrol_user($student->id, $course->id, 'student');
        $page = $g->create_module('page', ['course' => $course->id, 'name' => 'Activity <tag> & A',
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $this->grade($course, $student, 'Stored result', 82.5);
        $DB->insert_record('course_modules_completion', ['coursemoduleid' => $page->cmid,
            'userid' => $student->id, 'completionstate' => COMPLETION_COMPLETE_PASS, 'timemodified' => 1700000000]);
        $criterion = $DB->insert_record('course_completion_criteria', ['course' => $course->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY, 'module' => 'page', 'moduleinstance' => $page->cmid]);
        $DB->insert_record('course_completion_crit_compl', ['course' => $course->id, 'userid' => $student->id,
            'criteriaid' => $criterion, 'timecompleted' => 1700000000]);
        $DB->insert_record('course_completions', ['course' => $course->id, 'userid' => $student->id,
            'timecompleted' => 1700000000, 'timeenrolled' => 1600000000, 'timestarted' => 1600000000]);
        $tables = ['grade_grades', 'course_completions', 'course_completion_crit_compl', 'course_modules_completion'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = $DB->get_records($table, [], 'id');
        }
        $dir = make_request_directory();
        $result = course_reports::write(get_fast_modinfo($course), $dir, time());
        $this->assertCount(5, $result['files']);
        $grades = file_get_contents($dir . '/report-grades.html');
        $this->assertStringContainsString('ReportStudent Alpha', $grades);
        $this->assertStringContainsString('82.50', $grades);
        $this->assertStringContainsString('Activity &lt;tag&gt; &amp; A', file_get_contents($dir . '/report-progress.html'));
        $this->assertStringContainsString(
            get_string('reportspassed', 'local_flatarchive'),
            file_get_contents($dir . '/report-progress.html')
        );
        $this->assertStringContainsString(userdate(1700000000), file_get_contents($dir . '/report-completion.html'));
        foreach ($tables as $table) {
            $this->assertEquals($before[$table], $DB->get_records($table, [], 'id'));
        }
    }

    /**
     * Course archive permission alone does not grant permission to view other people's reports.
     */
    public function test_missing_report_permissions_do_not_create_files(): void {
        global $DB;
        $g = $this->getDataGenerator();
        $course = $g->create_course();
        $student = $g->create_user();
        $g->enrol_user($student->id, $course->id, 'student');
        $role = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        assign_capability('local/flatarchive:archive', CAP_ALLOW, $role, \context_course::instance($course->id)->id);
        assign_capability('moodle/course:viewparticipants', CAP_PROHIBIT, $role, \context_course::instance($course->id)->id);
        $this->setUser($student);
        $dir = make_request_directory();
        $result = reports::write(get_fast_modinfo($course), [], $dir);
        $this->assertSame(['reports.html'], array_keys($result['files']));
        $this->assertStringNotContainsString('report-participants.html', $result['link']);
        $this->assertStringNotContainsString('report-grades.html', $result['link']);
        $html = file_get_contents($dir . '/reports.html');
        $this->assertStringContainsString(get_string('reportsdenied', 'local_flatarchive'), $html);
        $this->assertStringNotContainsString('href="report-grades.html"', $html);
    }

    /**
     * Hidden items, hidden student grades and aggregates remain protected without viewhidden.
     */
    public function test_hidden_grades_are_not_disclosed(): void {
        global $DB;
        $g = $this->getDataGenerator();
        $course = $g->create_course();
        $student = $g->create_user();
        $g->enrol_user($student->id, $course->id, 'student');
        $this->grade($course, $student, 'HIDDEN-SECRET-ITEM', 73.25, 1);
        $item = $this->grade($course, $student, 'Visible item', 61.75);
        $DB->set_field('grade_grades', 'hidden', 1, ['itemid' => $item->id]);
        $teacher = $g->create_user();
        $g->enrol_user($teacher->id, $course->id, 'editingteacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('moodle/grade:viewhidden', CAP_PROHIBIT, $role, \context_course::instance($course->id)->id);
        $this->setUser($teacher);
        $dir = make_request_directory();
        course_reports::write(get_fast_modinfo($course), $dir, time());
        $html = file_get_contents($dir . '/report-grades.html');
        $this->assertStringNotContainsString('HIDDEN-SECRET-ITEM', $html);
        $this->assertStringNotContainsString('73.25', $html);
        $this->assertStringNotContainsString('61.75', $html);
        $this->assertStringContainsString('Visible item', $html);
        $this->assertStringContainsString(get_string('reportshiddentotals', 'local_flatarchive'), $html);
    }

    /**
     * A restricted teacher sees only the selected permitted group, not the neighbouring group.
     */
    public function test_separate_group_boundary(): void {
        global $DB, $SESSION;
        $g = $this->getDataGenerator();
        $course = $g->create_course(['groupmode' => SEPARATEGROUPS, 'groupmodeforce' => 1]);
        $teacher = $g->create_user();
        $g->enrol_user($teacher->id, $course->id, 'editingteacher');
        $groups = [];
        foreach (['Allowed', 'Private'] as $name) {
            $group = $g->create_group(['courseid' => $course->id]);
            $groups[] = $group;
            $student = $g->create_user(['firstname' => $name, 'lastname' => 'Student']);
            $g->enrol_user($student->id, $course->id, 'student');
            $g->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
            $this->grade($course, $student, $name . ' grade', 50);
        }
        $g->create_group_member(['groupid' => $groups[0]->id, 'userid' => $teacher->id]);
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $role, \context_course::instance($course->id)->id);
        $this->setUser($teacher);
        $SESSION->activegroup[$course->id][SEPARATEGROUPS][$course->defaultgroupingid] = $groups[0]->id;
        $dir = make_request_directory();
        course_reports::write(get_fast_modinfo($course), $dir, time());
        $html = file_get_contents($dir . '/report-grades.html');
        $this->assertStringContainsString('Allowed Student', $html);
        $this->assertStringNotContainsString('Private Student', $html);
        $participants = file_get_contents($dir . '/report-participants.html');
        $this->assertStringContainsString('Allowed Student', $participants);
        $this->assertStringNotContainsString('Private Student', $participants);
    }

    /**
     * Participation counts retained eligible events, excluding anonymous/admin/future/other-course events.
     */
    public function test_participation_filters_and_missing_log_reader(): void {
        global $DB;
        $g = $this->getDataGenerator();
        $course = $g->create_course();
        $other = $g->create_course();
        $page = $g->create_module('page', ['course' => $course->id]);
        $student = $g->create_user(['firstname' => 'LogParticipant', 'lastname' => 'Student']);
        $g->enrol_user($student->id, $course->id, 'student');
        $teacher = $g->create_user();
        $g->enrol_user($teacher->id, $course->id, 'editingteacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('moodle/site:viewanonymousevents', CAP_PROHIBIT, $role, \context_course::instance($course->id)->id);
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        get_log_manager(true);
        $context = \context_module::instance($page->cmid);
        $now = time();
        $record = ['eventname' => '\\mod_page\\event\\course_module_viewed', 'component' => 'mod_page',
            'action' => 'viewed', 'target' => 'course_module', 'objecttable' => 'page', 'objectid' => $page->id,
            'crud' => 'r', 'edulevel' => \core\event\base::LEVEL_PARTICIPATING,
            'contextid' => $context->id, 'contextlevel' => CONTEXT_MODULE, 'contextinstanceid' => $page->cmid,
            'userid' => $student->id, 'courseid' => $course->id, 'anonymous' => 0, 'other' => '{}',
            'timecreated' => $now - 30, 'origin' => 'web', 'ip' => '192.0.2.123'];
        foreach (
            [[], [], ['crud' => 'c'], ['anonymous' => 1, 'timecreated' => $now - 29],
                ['edulevel' => \core\event\base::LEVEL_OTHER, 'timecreated' => $now - 28],
                ['courseid' => $other->id, 'timecreated' => $now - 27],
                ['timecreated' => $now + 100], ['origin' => 'cli', 'timecreated' => $now - 26]] as $changes
        ) {
            $DB->insert_record('logstore_standard_log', $changes + $record);
        }
        $this->setUser($teacher);
        $dir = make_request_directory();
        course_reports::write(get_fast_modinfo($course), $dir, $now);
        $html = file_get_contents($dir . '/report-participation.html');
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $rows = (new \DOMXPath($dom))->query('//tbody/tr');
        $this->assertSame(1, $rows->length);
        $cells = $rows->item(0)->getElementsByTagName('td');
        $this->assertSame('1', $cells->item(1)->textContent);
        $this->assertSame('1', $cells->item(2)->textContent);
        $this->assertStringNotContainsString('192.0.2.123', $html);
        set_config('enabled_stores', '', 'tool_log');
        get_log_manager(true);
        course_reports::write(get_fast_modinfo($course), $dir, $now);
        $this->assertStringContainsString(
            get_string('reportsnologs', 'local_flatarchive'),
            file_get_contents($dir . '/report-participation.html')
        );
    }

    /**
     * Participants include teachers, multiple aliased roles and suspended enrolments, but not outsiders.
     */
    public function test_participant_roles_and_status(): void {
        global $DB;
        $g = $this->getDataGenerator();
        $course = $g->create_course();
        $context = \context_course::instance($course->id);
        $student = $g->create_user(['firstname' => 'Roster', 'lastname' => 'Student']);
        $teacher = $g->create_user(['firstname' => 'Roster', 'lastname' => 'Teacher']);
        $inactive = $g->create_user(['firstname' => 'Suspended', 'lastname' => 'Participant']);
        $g->create_user(['firstname' => 'Outside', 'lastname' => 'Person']);
        $g->enrol_user($student->id, $course->id, 'student');
        $g->enrol_user($teacher->id, $course->id, 'editingteacher');
        $g->enrol_user($inactive->id, $course->id, 'student');
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, ['userid' => $inactive->id]);
        $role = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $DB->insert_record('role_names', ['roleid' => $role->id, 'contextid' => $context->id, 'name' => 'Learner & reader']);
        role_assign($role->id, $teacher->id, $context->id);
        $dir = make_request_directory();
        course_reports::write(get_fast_modinfo($course), $dir, time());
        $html = file_get_contents($dir . '/report-participants.html');
        $this->assertStringContainsString('Roster Student', $html);
        $this->assertStringContainsString('Roster Teacher', $html);
        $this->assertStringContainsString('Suspended Participant', $html);
        $this->assertStringNotContainsString('Outside Person', $html);
        $this->assertStringContainsString('Learner &amp; reader', $html);
        $this->assertStringContainsString(get_string('defaultcourseteacher'), $html);
        $this->assertStringContainsString(get_string('archiveparticipantinactive', 'local_flatarchive'), $html);
        $this->assertStringContainsString(get_string('archiveparticipantactive', 'local_flatarchive'), $html);
    }

    /**
     * Report navigation remains local after ZIP layout conversion; unselected activities are absent.
     */
    public function test_index_links_after_layout(): void {
        $g = $this->getDataGenerator();
        $course = $g->create_course();
        $choice = $g->create_module('choice', ['course' => $course->id, 'name' => 'Selected & Choice', 'option' => ['Yes', 'No']]);
        $g->create_module('choice', ['course' => $course->id, 'name' => 'Unselected Choice', 'option' => ['Yes', 'No']]);
        $modinfo = get_fast_modinfo($course);
        $dir = make_request_directory();
        $export = choice_exporter::render($modinfo->get_cm($choice->cmid), $modinfo, $dir);
        $result = reports::write($modinfo, [$choice->cmid => $export], $dir);
        $html = local_flatarchive_render_index_template(
            $course,
            $course->id,
            [],
            '<p><a href="course-settings.html">Settings</a></p>',
            $result['link']
        );
        file_put_contents($dir . '/index.html', $html);
        file_put_contents($dir . '/course-settings.html', '<a href="index.html">Course</a>');
        $files = $result['files'] + ['index.html' => $dir . '/index.html',
            'course-settings.html' => $dir . '/course-settings.html', $export['filename'] => $dir . '/' . $export['filename']];
        $mapped = layout::organise($files);
        $root = file_get_contents($mapped['index.html']);
        $this->assertStringContainsString('href="resources/reports.html"', $root);
        $dom = new \DOMDocument();
        @$dom->loadHTML($root);
        $links = (new \DOMXPath($dom))->query('//nav[@class="fa-course-tools"]/a');
        $this->assertSame(4, $links->length);
        foreach (['course-settings.html', 'report-participants.html', 'report-grades.html', 'reports.html'] as $i => $file) {
            $this->assertSame('resources/' . $file, $links->item($i)->getAttribute('href'));
        }
        $index = file_get_contents($mapped['resources/reports.html']);
        $this->assertStringContainsString($export['filename'] . '#fa-activity-reports', $index);
        $this->assertStringNotContainsString('Unselected Choice', $index);
        $activity = file_get_contents($dir . '/' . $export['filename']);
        $this->assertStringContainsString('id="fa-activity-reports"', $activity);
        $this->assertStringContainsString('href="#fa-activity-reports"', $activity);
        $this->assertSame(1, substr_count($activity, 'class="fa-activity-reports-link"'));
        $detail = local_flatarchive_render_page_template($course, $modinfo->get_cm($choice->cmid), 'Detail', '<p>Result</p>');
        $this->assertStringNotContainsString('href="#fa-activity-reports"', $detail);
        $this->assertStringNotContainsString('href="report-grades.html"', $index);
        $this->assertStringContainsString('href="../index.html"', file_get_contents($dir . '/report-participants.html'));
        $this->assertStringContainsString('href="../index.html"', file_get_contents($dir . '/report-grades.html'));
    }
}
