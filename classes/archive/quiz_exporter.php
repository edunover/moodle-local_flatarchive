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
 * Offline Quiz documents using stored attempts and Moodle review permissions.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class quiz_exporter {
    /**
     * Export the questionnaire and permitted recorded attempts without saving question usages.
     *
     * @param \cm_info $cm Module.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Destination.
     * @return array Index entry and generated documents.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/question/engine/lib.php');
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'quiz') {
            throw new \coding_exception('The quiz exporter requires a quiz');
        }
        $context = \context_module::instance($cm->id);
        require_capability('mod/quiz:view', $context);
        if (!$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $quizobj = \mod_quiz\quiz_settings::create($cm->instance);
        $quiz = $quizobj->get_quiz();
        $filename = 'quiz-' . $cm->id . '.html';
        $files = [$filename];
        $intro = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $quiz->intro,
            $context,
            'mod_quiz',
            'intro',
            0,
            $directory,
            (int)$cm->id
        );
        $body = format_text($intro, $quiz->introformat, ['context' => $context, 'filter' => false]);
        $body .= \html_writer::tag('p', get_string('quizarchivescope', 'local_flatarchive'));
        $settings = [
            get_string('timelimit', 'quiz') => $quiz->timelimit ? format_time($quiz->timelimit) : get_string('unlimited'),
            get_string('attemptsallowed', 'quiz') => $quiz->attempts ?: get_string('unlimited'),
            get_string('grademethod', 'quiz') => quiz_get_grading_option_name($quiz->grademethod),
        ];
        foreach ($settings as $label => $value) {
            $body .= \html_writer::tag('p', s($label . ': ' . $value));
        }
        if (has_capability('mod/quiz:manage', $context)) {
            $questionnaire = self::questionnaire($quizobj, $cm, $directory);
            $file = 'quiz-' . $cm->id . '-questions.html';
            self::write($cm, $modinfo, $directory, $file, get_string('quizquestionnaire', 'local_flatarchive'), $questionnaire);
            $files[] = $file;
            $body .= \html_writer::tag('p', \html_writer::link($file, get_string('quizquestionnaire', 'local_flatarchive')));
        }
        $body .= \html_writer::tag('h2', get_string('quizattempts', 'local_flatarchive'));
        $reporter = has_capability('mod/quiz:viewreports', $context);
        $group = groups_get_activity_groupmode($cm) ? groups_get_activity_group($cm) : 0;
        $users = $reporter ? get_enrolled_users($context, '', $group, 'u.*', 'u.id', 0, 0, true) : [$USER];
        $links = [];
        foreach ($users as $user) {
            if ($user->deleted || ($reporter && !groups_user_groups_visible($cm->course, $user->id, $cm))) {
                continue;
            }
            $rows = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id, 'userid' => $user->id, 'preview' => 0], 'attempt, id');
            foreach ($rows as $row) {
                $attempt = $quizobj->create_attempt_object($row);
                if ($attempt->is_own_attempt()) {
                    if (!$attempt->is_finished() || !has_capability('mod/quiz:reviewmyattempts', $context)) {
                        continue;
                    }
                } else if (!$attempt->is_review_allowed()) {
                    continue;
                }
                $options = clone $attempt->get_display_options(true);
                if (!$options->attempt) {
                    continue;
                }
                $title = fullname($user, has_capability('moodle/site:viewfullnames', $context)) . ' — ' .
                    get_string('lessonattemptnumber', 'local_flatarchive', $row->attempt);
                $content = \html_writer::tag('p', s(get_string('state' . $row->state, 'quiz')));
                foreach (['timestart', 'timefinish'] as $field) {
                    if ($row->$field) {
                        $label = get_string($field === 'timestart' ? 'lessonstarted' : 'lessoncompleted', 'local_flatarchive');
                        $content .= \html_writer::tag('p', s($label . ': ' . userdate($row->$field)));
                    }
                }
                if ($options->marks >= \question_display_options::MARK_AND_MAX && $row->sumgrades !== null) {
                    $mark = format_float($row->sumgrades, 2) . ' / ' . format_float($quiz->sumgrades, 2);
                    $content .= \html_writer::tag('p', get_string('quizstoredmarks', 'local_flatarchive', s($mark)));
                }
                foreach ($attempt->get_slots() as $slot) {
                    $heading = $attempt->get_heading_before_slot($slot);
                    if ($heading !== null && $heading !== '') {
                        $content .= \html_writer::tag('h2', s($heading));
                    }
                    $qa = $attempt->get_question_attempt($slot);
                    $content .= quiz_question::render($qa, clone $options, $attempt->get_question_number($slot), $cm, $directory);
                }
                if ($options->overallfeedback && $row->sumgrades !== null) {
                    $grade = quiz_rescale_grade($row->sumgrades, $quiz, false);
                    $feedback = '';
                    // Overall feedback files are handled separately from question-engine files.
                    foreach ($DB->get_records('quiz_feedback', ['quizid' => $quiz->id]) as $item) {
                        if ($grade >= $item->mingrade && $grade < $item->maxgrade) {
                            $feedback = \local_flatarchive_copy_pluginfiles_and_rewrite(
                                $item->feedbacktext,
                                $context,
                                'mod_quiz',
                                'feedback',
                                (int)$item->id,
                                $directory,
                                (int)$cm->id
                            );
                            $feedback = format_text($feedback, $item->feedbacktextformat, ['filter' => false]);
                        }
                    }
                    $content .= $feedback;
                }
                $file = 'quiz-' . $cm->id . '-attempt-' . $row->id . '.html';
                self::write($cm, $modinfo, $directory, $file, $title, $content);
                $files[] = $file;
                $links[] = \html_writer::link($file, s($title));
            }
        }
        $body .= $links ? \html_writer::alist($links) :
            \html_writer::tag('p', get_string('quiznoattempts', 'local_flatarchive'));
        self::write($cm, $modinfo, $directory, $filename, $cm->name, $body);
        return ['filename' => $filename, 'filenames' => $files,
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name)) .
                \local_flatarchive_visibility_badges($cm, $modinfo))];
    }

    /**
     * Render authored slots without creating a persisted preview or drawing random questions.
     *
     * @param \mod_quiz\quiz_settings $quizobj Quiz.
     * @param \cm_info $cm Module.
     * @param string $directory Destination.
     * @return string Questionnaire.
     */
    private static function questionnaire(\mod_quiz\quiz_settings $quizobj, \cm_info $cm, string $directory): string {
        $context = \context_module::instance($cm->id);
        $slots = \mod_quiz\question\bank\qbank_helper::get_question_structure($cm->instance, $context);
        $sections = [];
        foreach ($quizobj->get_sections() as $section) {
            $sections[$section->firstslot] = $section->heading;
        }
        $body = '';
        foreach ($slots as $slot) {
            if (!empty($sections[$slot->slot])) {
                $body .= \html_writer::tag('h2', s($sections[$slot->slot]));
            }
            $body .= \html_writer::tag('h3', get_string('quizslot', 'local_flatarchive', $slot->slot));
            if ($slot->qtype === 'random') {
                $body .= \html_writer::tag('p', get_string('quizrandomslot', 'local_flatarchive'));
                continue;
            }
            if (!is_numeric($slot->questionid) || !question_has_capability_on($slot, 'use')) {
                $body .= \html_writer::tag('p', get_string('quizquestionunavailable', 'local_flatarchive'));
                continue;
            }
            $question = \question_bank::load_question($slot->questionid);
            $body .= tags::render('core_question', 'question', (int)$question->id);
            if (!quiz_question::supported($question->qtype->name())) {
                $body .= \html_writer::tag('p', get_string('quizunsupported', 'local_flatarchive', s($question->qtype->name())));
                continue;
            }
            $qa = new \question_attempt($question, 0, null, $slot->maxmark);
            $qa->set_slot($slot->slot);
            $qa->start('deferredfeedback', 1);
            $options = new \question_display_options();
            $options->correctness = \question_display_options::HIDDEN;
            $options->feedback = \question_display_options::HIDDEN;
            $options->marks = \question_display_options::MAX_ONLY;
            $options->generalfeedback = \question_display_options::VISIBLE;
            $options->rightanswer = \question_display_options::VISIBLE;
            $body .= quiz_question::render($qa, $options, (string)$slot->slot, $cm, $directory);
        }
        return $body;
    }

    /**
     * Write one document with common index and Quiz navigation.
     *
     * @param \cm_info $cm Module.
     * @param \course_modinfo $info Course structure.
     * @param string $directory Destination.
     * @param string $file Filename.
     * @param string $title Title.
     * @param string $body Content.
     */
    private static function write(
        \cm_info $cm,
        \course_modinfo $info,
        string $directory,
        string $file,
        string $title,
        string $body
    ): void {
        if ($file !== 'quiz-' . $cm->id . '.html') {
            $body = \html_writer::tag('p', \html_writer::link(
                'quiz-' . $cm->id . '.html',
                get_string('backtoquiz', 'local_flatarchive')
            )) . $body;
        }
        $html = \local_flatarchive_render_page_template(
            $info->get_course(),
            $cm,
            $title,
            $body,
            '',
            \local_flatarchive_visibility_badges($cm, $info)
        );
        \local_flatarchive_write_file($directory . '/' . $file, $html);
    }
}
