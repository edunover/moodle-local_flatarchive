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

use local_flatarchive\archive\quiz_exporter;

/**
 * Quiz export permissions, stored attempts and portable question content.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\quiz_exporter
 * @covers \local_flatarchive\archive\quiz_question
 */
final class quiz_exporter_test extends \advanced_testcase {
    /**
     * Prepare the native question engine.
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/question/engine/lib.php');
    }

    /**
     * Create a Quiz and an enrolled student.
     *
     * @param array $types Question types.
     * @return array Quiz, student, questions.
     */
    private function fixture(array $types = ['shortanswer']): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quiz = $generator->create_module('quiz', ['course' => $course->id, 'attempts' => 0,
            'sumgrades' => count($types), 'grade' => 10, 'preferredbehaviour' => 'deferredfeedback']);
        $student = $generator->create_user(['firstname' => 'QuizStudent', 'lastname' => 'Example']);
        $generator->enrol_user($student->id, $course->id, 'student');
        $qg = $generator->get_plugin_generator('core_question');
        $category = $qg->create_question_category(['contextid' => \context_course::instance($course->id)->id]);
        $questions = [];
        foreach ($types as $type) {
            $question = $qg->create_question($type, null, ['category' => $category->id]);
            quiz_add_quiz_question($question->id, $quiz);
            $questions[] = $question;
        }
        return [$quiz, $student, $questions];
    }

    /**
     * Export all pages into an isolated directory.
     *
     * @param \stdClass $quiz Quiz.
     * @return array HTML, directory, result.
     */
    private function export(\stdClass $quiz): array {
        $info = get_fast_modinfo($quiz->course);
        $directory = make_request_directory();
        $result = quiz_exporter::render($info->get_cm($quiz->cmid), $info, $directory);
        $html = '';
        foreach ($result['filenames'] as $file) {
            $html .= file_get_contents($directory . '/' . $file);
        }
        return [$html, $directory, $result];
    }

    /**
     * All supported authored types are passive and exporting creates no stored usages.
     */
    public function test_questionnaire_and_no_writes(): void {
        global $DB;
        [$quiz, $student, $questions] = $this->fixture(
            ['description', 'multichoice', 'truefalse', 'shortanswer', 'numerical', 'match', 'essay']
        );
        $DB->set_field('question', 'generalfeedback', 'AuthoredGeneralFeedback', ['id' => $questions[1]->id]);
        \question_bank::notify_question_edited($questions[1]->id);
        $before = $DB->count_records('question_usages');
        [$html, $directory, $result] = $this->export($quiz);
        $this->assertCount(2, $result['filenames']);
        $this->assertStringContainsString('fa-quiz-question', $html);
        $this->assertStringContainsString('AuthoredGeneralFeedback', $html);
        $this->assertStringContainsString('fa-back-index', $html);
        foreach (['<input', '<form', '<button', '<select', '<textarea', '<script'] as $fragment) {
            $this->assertStringNotContainsString($fragment, $html);
        }
        $this->assertSame($before, $DB->count_records('question_usages'));
        $this->assertSame(0, $DB->count_records('quiz_attempts', ['quiz' => $quiz->id]));
    }

    /**
     * Two retries retain distinct responses and the exporter does not change marks or steps.
     */
    public function test_attempts_are_separate_and_read_only(): void {
        global $DB;
        [$quiz, $student] = $this->fixture();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $this->setUser($student);
        $first = $generator->create_attempt($quiz->id, $student->id);
        $generator->submit_responses($first->id, [1 => 'FirstPrivateAnswer'], false, true);
        $second = $generator->create_attempt($quiz->id, $student->id);
        $generator->submit_responses($second->id, [1 => 'SecondPrivateAnswer'], false, true);
        $this->setAdminUser();
        $before = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id]);
        $steps = $DB->count_records('question_attempt_steps');
        [$html, $directory, $result] = $this->export($quiz);
        $this->assertCount(4, $result['filenames']);
        $page = file_get_contents($directory . '/quiz-' . $quiz->cmid . '-attempt-' . $first->id . '.html');
        $this->assertStringContainsString('FirstPrivateAnswer', $page);
        $this->assertStringNotContainsString('SecondPrivateAnswer', $page);
        $this->assertStringContainsString('SecondPrivateAnswer', $html);
        $this->assertEquals($before, $DB->get_records('quiz_attempts', ['quiz' => $quiz->id]));
        $this->assertSame($steps, $DB->count_records('question_attempt_steps'));
    }

    /**
     * Students cannot obtain the authored questionnaire or other participants' attempts.
     */
    public function test_review_options_and_own_attempt(): void {
        global $DB;
        [$quiz, $student] = $this->fixture();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $this->setUser($student);
        $attempt = $generator->create_attempt($quiz->id, $student->id);
        $generator->submit_responses($attempt->id, [1 => 'PrivateStudentAnswer'], false, true);
        $DB->set_field('quiz', 'reviewattempt', 0, ['id' => $quiz->id]);
        [$html, $directory, $result] = $this->export($quiz);
        $this->assertCount(1, $result['filenames']);
        $this->assertStringNotContainsString('PrivateStudentAnswer', $html);
        $this->assertStringNotContainsString('-questions.html', $html);
        $DB->set_field('quiz', 'reviewattempt', 0x11110, ['id' => $quiz->id]);
        [$html, $directory, $result] = $this->export($quiz);
        $this->assertCount(2, $result['filenames']);
        $this->assertStringContainsString('PrivateStudentAnswer', $html);
    }

    /**
     * Report restrictions prevent another teacher from exporting private responses.
     */
    public function test_report_permission_and_separate_groups(): void {
        global $DB;
        [$quiz, $student] = $this->fixture();
        $generator = $this->getDataGenerator();
        $this->setUser($student);
        $attempt = $generator->get_plugin_generator('mod_quiz')->create_attempt($quiz->id, $student->id);
        $generator->get_plugin_generator('mod_quiz')->submit_responses(
            $attempt->id,
            [1 => 'HiddenPrivateResponse'],
            false,
            true
        );
        $this->setAdminUser();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $quiz->course, 'editingteacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        $context = \context_module::instance($quiz->cmid);
        assign_capability('mod/quiz:viewreports', CAP_PROHIBIT, $role, $context->id);
        $this->setUser($teacher);
        [$html] = $this->export($quiz);
        $this->assertStringNotContainsString('HiddenPrivateResponse', $html);
        $this->setAdminUser();
        assign_capability('mod/quiz:viewreports', CAP_ALLOW, $role, $context->id);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $role, $context->id);
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $quiz->cmid]);
        $own = $generator->create_group(['courseid' => $quiz->course]);
        $foreign = $generator->create_group(['courseid' => $quiz->course]);
        $generator->create_group_member(['groupid' => $own->id, 'userid' => $teacher->id]);
        $generator->create_group_member(['groupid' => $foreign->id, 'userid' => $student->id]);
        rebuild_course_cache($quiz->course, true);
        $this->setUser($teacher);
        [$html] = $this->export($quiz);
        $this->assertStringNotContainsString('HiddenPrivateResponse', $html);
    }

    /**
     * Authored question media is copied locally and pluginfile placeholders are removed.
     */
    public function test_question_media(): void {
        global $DB;
        [$quiz, $student, $questions] = $this->fixture();
        $question = $questions[0];
        $context = \context_course::instance($quiz->course);
        get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'question',
            'filearea' => 'questiontext', 'itemid' => $question->id, 'filepath' => '/', 'filename' => 'image.png'], 'QuizMedia');
        $DB->set_field(
            'question',
            'questiontext',
            '<p>Illustration</p><img src="@@PLUGINFILE@@/image.png" alt="Image">',
            ['id' => $question->id]
        );
        \question_bank::notify_question_edited($question->id);
        [$html, $directory] = $this->export($quiz);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
        $this->assertStringNotContainsString('pluginfile.php', $html);
        $this->assertStringContainsString('files/' . $quiz->cmid . '/quiz/', $html);
        $files = glob($directory . '/files/' . $quiz->cmid . '/quiz/*/image.png');
        $this->assertCount(1, $files);
        $this->assertSame('QuizMedia', file_get_contents($files[0]));
    }

    /**
     * Essay text, embedded files and a saved teacher comment survive without grading writes.
     */
    public function test_essay_and_manual_comment(): void {
        global $DB;
        [$quiz, $student, $questions] = $this->fixture(['essay']);
        $DB->set_field('qtype_essay_options', 'responseformat', 'editorfilepicker', ['questionid' => $questions[0]->id]);
        \question_bank::notify_question_edited($questions[0]->id);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $this->setUser($student);
        $attempt = $generator->create_attempt($quiz->id, $student->id);
        $attemptobj = \mod_quiz\quiz_attempt::create($attempt->id);
        $attemptobj->get_question_usage()->process_action(1, [
            'answer' => '<p>PrivateEssayText</p><img src="@@PLUGINFILE@@/essay.png" alt="Essay">',
            'answerformat' => FORMAT_HTML,
        ]);
        $attemptobj->process_finish(time(), false);
        $attemptobj = \mod_quiz\quiz_attempt::create($attempt->id);
        $qa = $attemptobj->get_question_attempt(1);
        $step = $qa->get_last_step_with_qt_var('answer');
        $context = \context_module::instance($quiz->cmid);
        get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'question',
            'filearea' => 'response_answer', 'itemid' => $step->get_id(), 'filepath' => '/',
            'filename' => 'essay.png'], 'PrivateEssayMedia');
        $this->setAdminUser();
        $usage = $attemptobj->get_question_usage();
        $usage->manual_grade(1, 'TeacherCorrectionText', 0.5, FORMAT_HTML);
        \question_engine::save_questions_usage_by_activity($usage);
        [$html, $directory] = $this->export($quiz);
        $this->assertStringContainsString('PrivateEssayText', $html);
        $this->assertStringContainsString('TeacherCorrectionText', $html);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
        $files = glob($directory . '/files/' . $quiz->cmid . '/quiz/*/essay.png');
        $this->assertCount(1, $files);
        $this->assertSame('PrivateEssayMedia', file_get_contents($files[0]));
    }

    /**
     * A visible response must not reveal a solution or feedback disabled by review options.
     */
    public function test_hidden_solutions(): void {
        global $DB;
        [$quiz, $student, $questions] = $this->fixture();
        $DB->set_field('question', 'generalfeedback', 'SecretGeneralFeedback', ['id' => $questions[0]->id]);
        \question_bank::notify_question_edited($questions[0]->id);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $this->setUser($student);
        $attempt = $generator->create_attempt($quiz->id, $student->id);
        $generator->submit_responses($attempt->id, [1 => 'VisibleOwnResponse'], false, true);
        $DB->set_field('quiz', 'reviewattempt', 0x11110, ['id' => $quiz->id]);
        foreach (['reviewgeneralfeedback', 'reviewrightanswer', 'reviewmarks', 'reviewspecificfeedback'] as $field) {
            $DB->set_field('quiz', $field, 0, ['id' => $quiz->id]);
        }
        [$html] = $this->export($quiz);
        $this->assertStringContainsString('VisibleOwnResponse', $html);
        $this->assertStringNotContainsString('SecretGeneralFeedback', $html);
        $this->assertStringNotContainsString('rightanswer', $html);
        $this->assertStringNotContainsString('Stored points:', $html);
    }
}
