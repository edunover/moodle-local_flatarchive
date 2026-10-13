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

use local_flatarchive\archive\workshop_exporter;

/**
 * Workshop native grading, attachment preservation and private report boundaries.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\workshop_exporter
 */
final class workshop_exporter_test extends \advanced_testcase {
    /**
     * Create an instructor, two learners, a submission and its assessment.
     *
     * @param string $strategy Native strategy.
     * @return array Fixture objects.
     */
    private function fixture(string $strategy = 'accumulative'): array {
        $this->setAdminUser();
        $g = $this->getDataGenerator();
        $course = $g->create_course();
        $activity = $g->create_module('workshop', ['course' => $course->id, 'strategy' => $strategy,
            'grade' => 80, 'gradinggrade' => 20, 'instructauthors' => 'Author instructions',
            'instructreviewers' => 'Reviewer instructions', 'conclusion' => 'Workshop conclusion']);
        $author = $g->create_user(['firstname' => 'Uniqueauthor']);
        $reviewer = $g->create_user(['firstname' => 'Uniquereviewer']);
        $teacher = $g->create_user();
        foreach ([$author, $reviewer] as $student) {
            $g->enrol_user($student->id, $course->id, 'student');
        }
        $g->enrol_user($teacher->id, $course->id, 'editingteacher');
        $wg = $g->get_plugin_generator('mod_workshop');
        $submission = $wg->create_submission($activity->id, $author->id, [
            'title' => 'Submission title', 'content' => '<p>Original work<img src="@@PLUGINFILE@@/image.png"></p>',
            'contentformat' => FORMAT_HTML, 'grade' => 75, 'gradeover' => 0,
            'feedbackauthor' => 'Teacher to author', 'feedbackauthorformat' => FORMAT_HTML,
        ]);
        $assessment = $wg->create_assessment($submission, $reviewer->id, [
            'grade' => 50, 'gradinggrade' => 80, 'gradinggradeover' => 100,
            'feedbackauthor' => 'Peer feedback', 'feedbackauthorformat' => FORMAT_HTML,
            'feedbackreviewer' => 'Teacher private feedback', 'feedbackreviewerformat' => FORMAT_HTML,
        ]);
        return [$course, $activity, $author, $reviewer, $teacher, $submission, $assessment];
    }

    /**
     * Copy rich text and attachments; keep zero overrides and recorded grades without mutation.
     */
    public function test_submissions_assessments_and_files(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $author, $reviewer, $teacher, $submission, $assessment] = $this->fixture();
        $context = \context_module::instance($activity->cmid);
        foreach (
            ['submission_content' => [$submission, 'image.png'], 'submission_attachment' => [$submission, 'work.txt'],
            'overallfeedback_attachment' => [$assessment, 'feedback.txt']] as $area => [$id, $name]
        ) {
            get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'mod_workshop',
                'filearea' => $area, 'itemid' => $id, 'filepath' => '/', 'filename' => $name], $area . ' bytes');
        }
        $DB->insert_record('workshop_aggregations', [
            'workshopid' => $activity->id, 'userid' => $reviewer->id, 'gradinggrade' => 80,
        ]);
        $before = $DB->get_record('workshop_submissions', ['id' => $submission]);
        $this->setUser($teacher);
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('flatarchive-workshop-' . random_string());
        $result = workshop_exporter::render($modinfo->get_cm($activity->cmid), $modinfo, $directory);
        $this->assertCount(3, $result['filenames']);
        $prefix = 'workshop-' . $activity->cmid;
        $html = file_get_contents($directory . '/' . $prefix . '-submission-' . $submission . '.html');
        foreach (['Uniqueauthor', 'Original work', '60.00 / 80', '0.00 / 80', 'Teacher to author', 'work.txt'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $html = file_get_contents($directory . '/' . $prefix . '-assessment-' . $assessment . '.html');
        foreach (['Uniquereviewer', 'Peer feedback', 'Teacher private feedback', '40.00 / 80', '20.00 / 20'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertSame('submission_attachment bytes', file_get_contents(
            $directory . '/files/' . $activity->cmid . '/submission_attachment/' . $submission . '/work.txt'
        ));
        $this->assertSame('overallfeedback_attachment bytes', file_get_contents(
            $directory . '/files/' . $activity->cmid . '/overallfeedback_attachment/' . $assessment . '/feedback.txt'
        ));
        $this->assertEquals($before, $DB->get_record('workshop_submissions', ['id' => $submission]));
        $this->assertStringContainsString('href="index.html"', $html);
        $this->assertStringNotContainsString('[[', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    /**
     * All four native strategies preserve descriptions, selected grades and comments.
     */
    public function test_native_strategies(): void {
        global $DB;
        $this->resetAfterTest();
        foreach (['accumulative', 'comments', 'numerrors', 'rubric'] as $strategy) {
            [$course, $activity, $author, $reviewer, $teacher, $submission, $assessment] = $this->fixture($strategy);
            $dimension = ['workshopid' => $activity->id, 'sort' => 1,
                'description' => 'Criterion description', 'descriptionformat' => FORMAT_HTML];
            if ($strategy === 'accumulative') {
                $dimension['grade'] = 10;
            } else if ($strategy === 'numerrors') {
                $dimension += ['grade0' => 'Missing', 'grade1' => 'Present'];
            }
            $id = $DB->insert_record('workshopform_' . $strategy, $dimension);
            if ($strategy === 'rubric') {
                $DB->insert_record('workshopform_rubric_levels', ['dimensionid' => $id, 'grade' => 1,
                    'definition' => 'Chosen level', 'definitionformat' => FORMAT_HTML]);
            }
            if ($strategy === 'numerrors') {
                $DB->insert_record('workshopform_numerrors_map', ['workshopid' => $activity->id, 'nonegative' => 1, 'grade' => 50]);
            }
            $DB->insert_record('workshop_grades', ['assessmentid' => $assessment, 'strategy' => $strategy,
                'dimensionid' => $id, 'grade' => 1, 'peercomment' => 'Criterion feedback', 'peercommentformat' => FORMAT_HTML]);
            $modinfo = get_fast_modinfo($course);
            $directory = make_temp_directory('flatarchive-workshop-' . random_string());
            workshop_exporter::render($modinfo->get_cm($activity->cmid), $modinfo, $directory);
            $html = file_get_contents($directory . '/workshop-' . $activity->cmid . '-assessment-' . $assessment . '.html');
            $this->assertStringContainsString('Criterion description', $html);
            $this->assertStringContainsString('Criterion feedback', $html);
            if ($strategy === 'rubric') {
                $this->assertStringContainsString('Chosen level', $html);
                $this->assertStringContainsString('Matches recorded score', $html);
            } else if ($strategy === 'numerrors') {
                $this->assertStringContainsString('Present', $html);
                $this->assertStringContainsString('50%', $html);
            }
        }
    }

    /**
     * Names and assessment records obey independent capabilities.
     */
    public function test_private_reports_and_names(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $author, $reviewer, $teacher, $submission, $assessment] = $this->fixture();
        $context = \context_module::instance($activity->cmid);
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        foreach (['viewauthornames', 'viewreviewernames', 'overridegrades'] as $cap) {
            assign_capability('mod/workshop:' . $cap, CAP_PROHIBIT, $role, $context->id);
        }
        $this->setUser($teacher);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($activity->cmid);
        $directory = make_temp_directory('flatarchive-workshop-private-' . random_string());
        $result = workshop_exporter::render($cm, $modinfo, $directory);
        foreach ($result['filenames'] as $filename) {
            $html = file_get_contents($directory . '/' . $filename);
            foreach (['Uniqueauthor', 'Uniquereviewer', 'Teacher private feedback'] as $secret) {
                $this->assertStringNotContainsString($secret, $html);
            }
        }
        assign_capability('mod/workshop:viewallassessments', CAP_PROHIBIT, $role, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $directory = make_temp_directory('flatarchive-workshop-noassess-' . random_string());
        $result = workshop_exporter::render($cm, $modinfo, $directory);
        $this->assertCount(2, $result['filenames']);
        assign_capability('mod/workshop:viewallsubmissions', CAP_PROHIBIT, $role, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $directory = make_temp_directory('flatarchive-workshop-nosub-' . random_string());
        $result = workshop_exporter::render($cm, $modinfo, $directory);
        $this->assertCount(1, $result['filenames']);
        $this->assertFileDoesNotExist($directory . '/files/' . $cm->id . '/submission_content');
    }

    /**
     * Examples and pending allocations remain visible to their manager; groups restrict participant work.
     */
    public function test_examples_pending_and_separate_groups(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $author, $reviewer, $teacher, $submission, $assessment] = $this->fixture();
        $g = $this->getDataGenerator();
        $wg = $g->get_plugin_generator('mod_workshop');
        $example = $wg->create_submission($activity->id, $teacher->id, ['example' => 1, 'title' => 'Reference example']);
        $pending = $wg->create_assessment($example, $teacher->id, ['timecreated' => 0, 'timemodified' => 0]);
        $group = $g->create_group(['courseid' => $course->id]);
        $g->create_group_member(['groupid' => $group->id, 'userid' => $teacher->id]);
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $activity->cmid]);
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $role, \context_module::instance($activity->cmid)->id);
        rebuild_course_cache($course->id, true);
        $this->setUser($teacher);
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('flatarchive-workshop-groups-' . random_string());
        $result = workshop_exporter::render($modinfo->get_cm($activity->cmid), $modinfo, $directory);
        $this->assertCount(3, $result['filenames']);
        $this->assertNotContains('workshop-' . $activity->cmid . '-submission-' . $submission . '.html', $result['filenames']);
        $html = file_get_contents($directory . '/workshop-' . $activity->cmid . '-assessment-' . $pending . '.html');
        $this->assertStringContainsString('Allocated, not yet assessed', $html);
        $this->setUser($author);
        $modinfo = get_fast_modinfo($course);
        $this->expectException(\required_capability_exception::class);
        workshop_exporter::render($modinfo->get_cm($activity->cmid), $modinfo, $directory);
    }
}
