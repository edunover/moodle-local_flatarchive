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
 * Read-only instructor archive of Workshop submissions and native assessment forms.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class workshop_exporter {
    /**
     * Export instructions, forms, submissions and permitted assessments.
     *
     * @param \cm_info $cm Activity.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Destination.
     * @return array Generated documents and index entry.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/workshop/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'workshop' || !$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        require_capability('mod/workshop:view', $cm->context);
        require_capability('moodle/course:manageactivities', $cm->context);
        $instance = $DB->get_record('workshop', ['id' => $cm->instance], '*', MUST_EXIST);
        $workshop = new \workshop($instance, $cm, $modinfo->get_course());
        $main = 'workshop-' . $cm->id . '.html';
        $filenames = [$main];
        $body = self::text($instance->intro, $instance->introformat, 'intro', 0, $cm, $directory);
        $body .= self::heading('wsscope', 'p');
        foreach (['instructauthors', 'instructreviewers', 'conclusion'] as $field) {
            $body .= self::heading('ws' . $field);
            $body .= self::text($instance->$field, $instance->{$field . 'format'}, $field, 0, $cm, $directory);
        }
        $body .= self::heading('wsform') . self::form($instance, $cm, $directory);
        $body .= reports::anchor() . self::heading('wssubmissions');
        $links = [];
        $aggregates = '';
        if (has_capability('mod/workshop:viewallsubmissions', $cm->context)) {
            $submissions = $DB->get_records('workshop_submissions', ['workshopid' => $instance->id], 'example DESC, id');
            foreach ($submissions as $submission) {
                if ($submission->example) {
                    if (!has_capability('mod/workshop:manageexamples', $cm->context)) {
                        continue;
                    }
                } else if (!$workshop->check_group_membership($submission->authorid)) {
                    continue;
                }
                $title = $submission->title;
                if ($submission->example) {
                    $title = get_string('wsexample', 'local_flatarchive') . ' — ' . $title;
                }
                $content = \html_writer::link($main, get_string('wsback', 'local_flatarchive'));
                $content .= self::field('wsauthor', self::name($submission->authorid, 'viewauthornames', $cm));
                $content .= self::field('wscreated', userdate($submission->timecreated));
                $content .= self::field('wsmodified', userdate($submission->timemodified));
                foreach (['late', 'published'] as $flag) {
                    $content .= self::field('ws' . $flag, get_string($submission->$flag ? 'yes' : 'no'));
                }
                $content .= self::text(
                    $submission->content,
                    $submission->contentformat,
                    'submission_content',
                    (int)$submission->id,
                    $cm,
                    $directory
                );
                $content .= self::files('submission_attachment', (int)$submission->id, $cm, $directory);
                $content .= self::field('wssubmissiongrade', self::score($submission->grade, $instance->grade));
                if ($submission->gradeover !== null) {
                    $content .= self::field('wsoverride', self::score($submission->gradeover, $instance->grade));
                }
                $content .= self::heading('wsfeedbackauthor');
                $content .= self::plain($submission->feedbackauthor, $submission->feedbackauthorformat);
                if (has_capability('mod/workshop:viewallassessments', $cm->context)) {
                    $assessments = $DB->get_records('workshop_assessments', ['submissionid' => $submission->id], 'id');
                    $assessmentlinks = [];
                    foreach ($assessments as $assessment) {
                        // Example practice belongs to each reviewer; reference assessments have weight 1.
                        if (
                            $submission->example && !$assessment->weight &&
                            !$workshop->check_group_membership($assessment->reviewerid)
                        ) {
                            continue;
                        }
                        if (!$submission->example) {
                            $workshop->check_view_assessment($assessment, $submission);
                        }
                        $assessmentfile = 'workshop-' . $cm->id . '-assessment-' . $assessment->id . '.html';
                        $submissionfile = 'workshop-' . $cm->id . '-submission-' . $submission->id . '.html';
                        $assessmenttitle = get_string('wsassessment', 'local_flatarchive', count($assessmentlinks) + 1);
                        $report = \html_writer::link($submissionfile, s($title));
                        $report .= self::assessment($instance, $assessment, $cm, $directory);
                        self::write($cm, $modinfo, $directory, $assessmentfile, $assessmenttitle, $report);
                        $filenames[] = $assessmentfile;
                        $assessmentlinks[] = \html_writer::link($assessmentfile, s($assessmenttitle));
                    }
                    $content .= self::heading('wsassessments') . \html_writer::alist($assessmentlinks);
                } else {
                    $content .= self::heading('wsrestricted', 'p');
                }
                $filename = 'workshop-' . $cm->id . '-submission-' . $submission->id . '.html';
                self::write($cm, $modinfo, $directory, $filename, $title, $content);
                $filenames[] = $filename;
                $links[] = \html_writer::link($filename, s($title));
            }
            if (has_capability('mod/workshop:viewallassessments', $cm->context)) {
                $aggregates = self::aggregations($instance, $workshop, $cm);
            }
        } else {
            $body .= self::heading('wsrestricted', 'p');
        }
        $body .= $links ? \html_writer::alist($links) : self::heading('wsnosubmissions', 'p');
        $body .= $aggregates;
        self::write($cm, $modinfo, $directory, $main, $cm->name, $body);
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        return ['filename' => $main, 'filenames' => $filenames,
            'item' => \html_writer::tag('li', \html_writer::link($main, s($cm->name)) . $badges)];
    }

    /**
     * A recorded review, without changing allocation, phase, grades or completion.
     *
     * @param \stdClass $instance Activity.
     * @param \stdClass $assessment Stored assessment.
     * @param \cm_info $cm Module.
     * @param string $directory Destination.
     * @return string HTML.
     */
    private static function assessment(\stdClass $instance, \stdClass $assessment, \cm_info $cm, string $directory): string {
        $html = self::field('wsreviewer', self::name($assessment->reviewerid, 'viewreviewernames', $cm));
        $html .= self::field('wsweight', (string)$assessment->weight);
        if (!$assessment->timecreated) {
            return $html . self::heading('wspending', 'p');
        }
        $html .= self::field('wsmodified', userdate($assessment->timemodified));
        $html .= self::field('wssubmissiongrade', self::score($assessment->grade, $instance->grade));
        $html .= self::field('wsassessmentgrade', self::score($assessment->gradinggrade, $instance->gradinggrade));
        if ($assessment->gradinggradeover !== null) {
            $html .= self::field('wsoverride', self::score($assessment->gradinggradeover, $instance->gradinggrade));
        }
        $html .= self::form($instance, $cm, $directory, (int)$assessment->id);
        $html .= self::heading('wsfeedbackauthor');
        $html .= self::text(
            $assessment->feedbackauthor,
            $assessment->feedbackauthorformat,
            'overallfeedback_content',
            (int)$assessment->id,
            $cm,
            $directory
        );
        $html .= self::files('overallfeedback_attachment', (int)$assessment->id, $cm, $directory);
        if (has_capability('mod/workshop:overridegrades', $cm->context)) {
            $html .= self::heading('wsfeedbackreviewer');
            $html .= self::plain($assessment->feedbackreviewer, $assessment->feedbackreviewerformat);
        }
        return $html;
    }

    /**
     * Render the four native assessment strategies and optionally their recorded answers.
     *
     * @param \stdClass $instance Activity.
     * @param \cm_info $cm Module.
     * @param string $directory Destination.
     * @param int $assessmentid Optional recorded review.
     * @return string Static form.
     */
    private static function form(\stdClass $instance, \cm_info $cm, string $directory, int $assessmentid = 0): string {
        global $DB;
        $strategy = $instance->strategy;
        if (!in_array($strategy, ['accumulative', 'comments', 'numerrors', 'rubric'], true)) {
            return self::field('wsunsupported', $strategy);
        }
        $component = 'workshopform_' . $strategy;
        $html = self::field('wsstrategy', get_string('pluginname', $component));
        $dimensions = $DB->get_records($component, ['workshopid' => $instance->id], 'sort, id');
        $grades = $assessmentid ? $DB->get_records('workshop_grades', [
            'assessmentid' => $assessmentid, 'strategy' => $strategy,
        ]) : [];
        $bydimension = [];
        foreach ($grades as $grade) {
            $bydimension[$grade->dimensionid] = $grade;
        }
        foreach (array_values($dimensions) as $i => $dimension) {
            $html .= \html_writer::tag('h3', get_string('wscriterion', 'local_flatarchive', $i + 1));
            $html .= self::text(
                $dimension->description,
                $dimension->descriptionformat,
                'description',
                (int)$dimension->id,
                $cm,
                $directory,
                $component
            );
            $answer = $bydimension[$dimension->id] ?? null;
            $value = $answer->grade ?? null;
            if (isset($dimension->weight)) {
                $html .= self::field('wsweight', (string)$dimension->weight);
            }
            if ($strategy === 'rubric') {
                $levels = $DB->get_records('workshopform_rubric_levels', ['dimensionid' => $dimension->id], 'grade, id');
                foreach ($levels as $level) {
                    $label = (string)(float)$level->grade;
                    // Moodle stores the level score, not its ID; equal-score levels cannot be distinguished.
                    if ($value !== null && (float)$level->grade === (float)$value) {
                        $label .= ' — ' . get_string('wsmatchedlevel', 'local_flatarchive');
                    }
                    $html .= \html_writer::tag('p', s($label)) . self::plain($level->definition, $level->definitionformat);
                }
            } else if ($strategy === 'accumulative') {
                if ($dimension->grade < 0) {
                    $scale = $DB->get_field('scale', 'scale', ['id' => abs($dimension->grade)]);
                    $options = $scale === false ? [] : explode(',', $scale);
                    $html .= \html_writer::alist(array_map('s', $options), [], 'ol');
                    if ($value !== null && isset($options[(int)$value - 1])) {
                        $html .= self::field('wsrecorded', trim($options[(int)$value - 1]));
                    }
                } else {
                    $html .= self::field('wsmaximum', (string)$dimension->grade);
                }
            } else if ($strategy === 'numerrors') {
                $labels = [$dimension->grade0 ?: get_string('no'), $dimension->grade1 ?: get_string('yes')];
                $html .= \html_writer::alist(array_map('s', $labels));
                if ($value !== null && isset($labels[(int)$value])) {
                    $html .= self::field('wsrecorded', $labels[(int)$value]);
                }
            }
            if ($answer) {
                if ($strategy !== 'comments') {
                    $html .= self::field('wsrecordedvalue', $value === null ? get_string('wsnotgraded', 'local_flatarchive')
                        : (string)(float)$value);
                }
                $html .= self::heading('wscomment', 'h4') . self::plain($answer->peercomment, $answer->peercommentformat);
            } else if ($assessmentid) {
                $html .= self::heading('wsnoanswer', 'p');
            }
        }
        if ($strategy === 'numerrors') {
            $html .= self::heading('wserrormap');
            $mapping = $DB->get_records('workshopform_numerrors_map', ['workshopid' => $instance->id], 'nonegative');
            foreach ($mapping as $map) {
                $html .= \html_writer::tag('p', s($map->nonegative . ' → ' . (float)$map->grade . '%'));
            }
        }
        return $html;
    }

    /**
     * Preserve aggregate grades for assessment quality, without triggering aggregation.
     *
     * @param \stdClass $instance Activity.
     * @param \workshop $workshop Native manager.
     * @param \cm_info $cm Module.
     * @return string HTML.
     */
    private static function aggregations(\stdClass $instance, \workshop $workshop, \cm_info $cm): string {
        global $DB;
        $html = self::heading('wsaggregate');
        $rows = $DB->get_records('workshop_aggregations', ['workshopid' => $instance->id], 'id');
        foreach ($rows as $row) {
            if (!$workshop->check_group_membership($row->userid)) {
                continue;
            }
            $html .= \html_writer::tag('p', s(self::name($row->userid, 'viewreviewernames', $cm) . ' — ' .
                self::score($row->gradinggrade, $instance->gradinggrade)));
        }
        return $html;
    }

    /**
     * Preserve native name visibility without including profile links or user IDs.
     *
     * @param int $userid Person.
     * @param string $capability Name capability suffix.
     * @param \cm_info $cm Module.
     * @return string Plain name or anonymous label.
     */
    private static function name(int $userid, string $capability, \cm_info $cm): string {
        if (!has_capability('mod/workshop:' . $capability, $cm->context)) {
            return get_string('wsanonymous', 'local_flatarchive');
        }
        $user = \core_user::get_user($userid);
        return $user && !$user->deleted ? fullname($user) : get_string('wsanonymous', 'local_flatarchive');
    }

    /**
     * Express a stored percentage on its configured maximum; never aggregate new grades.
     *
     * @param mixed $percent Stored value or null.
     * @param mixed $maximum Maximum points.
     * @return string Readable score.
     */
    private static function score($percent, $maximum): string {
        if ($percent === null) {
            return get_string('wsnotgraded', 'local_flatarchive');
        }
        return format_float((float)$percent * (float)$maximum / 100, 2) . ' / ' . (float)$maximum .
            ' (' . format_float((float)$percent, 2) . '%)';
    }

    /**
     * Copy local text assets before formatting without dynamic filters.
     *
     * @param mixed $text Stored text.
     * @param mixed $format Moodle format.
     * @param string $area File area.
     * @param int $itemid Item ID.
     * @param \cm_info $cm Module.
     * @param string $directory Destination.
     * @param string $component File component.
     * @return string HTML.
     */
    private static function text(
        $text,
        $format,
        string $area,
        int $itemid,
        \cm_info $cm,
        string $directory,
        string $component = 'mod_workshop'
    ): string {
        $text = \local_flatarchive_copy_pluginfiles_and_rewrite(
            (string)$text,
            $cm->context,
            $component,
            $area,
            $itemid,
            $directory,
            (int)$cm->id
        );
        return self::plain($text, $format);
    }

    /**
     * Format stored feedback without Moodle filters.
     *
     * @param mixed $text Text.
     * @param mixed $format Format.
     * @return string HTML.
     */
    private static function plain($text, $format): string {
        return format_text((string)$text, (int)$format, ['filter' => false, 'noclean' => false]);
    }

    /**
     * Copy original attachments and list their local links.
     *
     * @param string $area File area.
     * @param int $itemid Item ID.
     * @param \cm_info $cm Module.
     * @param string $directory Destination.
     * @return string HTML.
     */
    private static function files(string $area, int $itemid, \cm_info $cm, string $directory): string {
        self::text('', FORMAT_HTML, $area, $itemid, $cm, $directory);
        $items = [];
        $files = get_file_storage()->get_area_files($cm->context->id, 'mod_workshop', $area, $itemid, 'filepath, filename', false);
        foreach ($files as $file) {
            $path = paths::assets($cm->id, $area, $itemid) . $file->get_filepath() . $file->get_filename();
            $url = implode('/', array_map('rawurlencode', explode('/', $path)));
            $items[] = \html_writer::link($url, s($file->get_filename()));
        }
        return $items ? \html_writer::alist($items) : '';
    }

    /**
     * Translated heading or notice.
     *
     * @param string $key String key.
     * @param string $tag HTML tag.
     * @return string HTML.
     */
    private static function heading(string $key, string $tag = 'h2'): string {
        return \html_writer::tag($tag, get_string($key, 'local_flatarchive'));
    }

    /**
     * Translated field with an escaped value.
     *
     * @param string $key Label.
     * @param string $value Plain value.
     * @return string HTML.
     */
    private static function field(string $key, string $value): string {
        return \html_writer::tag('p', \html_writer::tag('strong', get_string($key, 'local_flatarchive') . ': ') . s($value));
    }

    /**
     * Write one passive document with shared navigation and visibility badges.
     *
     * @param \cm_info $cm Module.
     * @param \course_modinfo $modinfo Course.
     * @param string $directory Destination.
     * @param string $filename Filename.
     * @param string $title Title.
     * @param string $body HTML.
     */
    private static function write(
        \cm_info $cm,
        \course_modinfo $modinfo,
        string $directory,
        string $filename,
        string $title,
        string $body
    ): void {
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $title, $body, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
    }
}
