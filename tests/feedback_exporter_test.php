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

use local_flatarchive\archive\feedback_exporter;

/**
 * Feedback offline content, response privacy and access boundaries.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\feedback_exporter
 */
final class feedback_exporter_test extends \advanced_testcase {
    /**
     * Set up a fresh Moodle fixture.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Insert a stored question fixture using the Feedback schema.
     *
     * @param \stdClass $feedback Activity record.
     * @param string $type Item type.
     * @param string $name Question title.
     * @param string $presentation Stored definition.
     * @param array $extra Overrides.
     * @return \stdClass Question record.
     */
    private function question(
        \stdClass $feedback,
        string $type,
        string $name,
        string $presentation = '',
        array $extra = []
    ): \stdClass {
        global $DB;
        $record = (object)($extra + ['feedback' => $feedback->id, 'template' => 0, 'name' => $name,
            'label' => '', 'presentation' => $presentation, 'typ' => $type,
            'hasvalue' => !in_array($type, ['label', 'pagebreak', 'captcha']),
            'position' => $DB->count_records('feedback_item', ['feedback' => $feedback->id]) + 1,
            'required' => 0, 'dependitem' => 0, 'dependvalue' => '', 'options' => '']);
        $record->id = $DB->insert_record('feedback_item', $record);
        return $record;
    }

    /**
     * Create one submitted response, using explicit anonymity per submission.
     *
     * @param \stdClass $feedback Activity record.
     * @param int $userid Respondent.
     * @param array $values Question ID to stored value.
     * @param bool $anonymous Anonymous submission.
     * @return int Completed ID.
     */
    private function response(\stdClass $feedback, int $userid, array $values, bool $anonymous = false): int {
        global $DB;
        $id = $DB->insert_record('feedback_completed', ['feedback' => $feedback->id, 'userid' => $userid,
            'timemodified' => 1700000000, 'random_response' => 0, 'anonymous_response' => $anonymous ? 1 : 2,
            'courseid' => 0]);
        foreach ($values as $item => $value) {
            $DB->insert_record('feedback_value', ['completed' => $id, 'item' => $item,
                'value' => $value, 'course_id' => 0, 'tmp_completed' => 0]);
        }
        return $id;
    }

    /**
     * Render and read all generated HTML.
     *
     * @param \stdClass $feedback Activity fixture.
     * @return array Directory, export metadata and HTML files.
     */
    private function export(\stdClass $feedback): array {
        $info = get_fast_modinfo($feedback->course);
        $directory = make_request_directory();
        $result = feedback_exporter::render($info->get_cm($feedback->cmid), $info, $directory);
        $pages = [];
        foreach ($result['filenames'] as $filename) {
            $pages[$filename] = file_get_contents($directory . '/' . $filename);
        }
        return [$directory, $result, $pages];
    }

    /**
     * Questions, conditions, media, choices and submitted answers survive a ZIP round trip.
     */
    public function test_questionnaire_files_and_responses(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $feedback = $generator->create_module('feedback', ['course' => $course->id,
            'intro' => '<p>Introduction</p>', 'introformat' => FORMAT_HTML]);
        $user = $generator->create_user(['firstname' => 'Alice', 'lastname' => 'Test']);
        $label = $this->question($feedback, 'label', 'Media', '<img src="@@PLUGINFILE@@/été.png" alt="Media test">');
        $choice = $this->question($feedback, 'multichoice', 'Choix multiples', 'c>>>>>Oui|Non|Peut-être', ['required' => 1]);
        $this->question($feedback, 'pagebreak', '');
        $rated = $this->question($feedback, 'multichoicerated', 'Note', 'r>>>>>1####Faible|5####Excellent');
        $numeric = $this->question($feedback, 'numeric', 'Nombre', '0|10');
        $text = $this->question(
            $feedback,
            'textarea',
            'Pourquoi ?',
            '40|5',
            ['dependitem' => $choice->id, 'dependvalue' => 'Oui']
        );
        $this->question($feedback, 'textfield', 'Court', '20|100');
        $this->question($feedback, 'captcha', 'Contrôle');
        $context = \context_module::instance($feedback->cmid);
        get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'mod_feedback',
            'filearea' => 'item', 'itemid' => $label->id, 'filepath' => '/', 'filename' => 'été.png'], 'Image bytes');
        $this->response($feedback, $user->id, [$text->id => '<script>bad()</script> été', $numeric->id => '0',
            $rated->id => '2', $choice->id => '1|3']);
        $DB->insert_record('feedback_completedtmp', ['feedback' => $feedback->id, 'userid' => $user->id, 'guestid' => '']);
        [$directory, $result, $pages] = $this->export($feedback);
        $this->assertCount(2, $pages);
        $index = $pages[$result['filename']];
        $this->assertStringContainsString('Pourquoi ?', $index);
        $this->assertStringContainsString('Excellent (5)', $index);
        $this->assertStringContainsString('Oui', $index);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $index);
        $response = $pages[$result['filenames'][1]];
        $this->assertStringContainsString('Alice Test', $response);
        $this->assertStringContainsString('Peut-être', $response);
        $this->assertStringContainsString('Excellent (5)', $response);
        $this->assertStringContainsString('&lt;script&gt;', $response);
        $this->assertStringNotContainsString('<script>', $response);
        $this->assertStringContainsString('2023-11-14', $response);
        $this->assertLessThan(strpos($response, 'Pourquoi ?'), strpos($response, 'Choix multiples'));
        $asset = 'files/' . $feedback->cmid . '/item/' . $label->id . '/été.png';
        $this->assertSame('Image bytes', file_get_contents($directory . '/' . $asset));
        $zipfiles = [];
        foreach (array_merge($result['filenames'], [$asset]) as $file) {
            $zipfiles[$file] = $directory . '/' . $file;
        }
        $packer = get_file_packer('application/zip');
        $zip = $directory . '/archive.zip';
        $this->assertTrue($packer->archive_to_pathname($zipfiles, $zip));
        $extracted = make_request_directory();
        $this->assertIsArray($packer->extract_to_pathname($zip, $extracted));
        $this->assertSame('Image bytes', file_get_contents($extracted . '/' . $asset));
    }

    /**
     * Per-response anonymity wins over the current activity setting; no private metadata leaks.
     */
    public function test_anonymous_and_foreign_values(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $feedback = $generator->create_module('feedback', ['course' => $course->id, 'anonymous' => 2]);
        $other = $generator->create_module('feedback', ['course' => $course->id]);
        $user = $generator->create_user(['firstname' => 'SECRETNAME', 'lastname' => 'PRIVATE']);
        $rated = $this->question($feedback, 'multichoicerated', 'Rating only', 'r>>>>>1####Low|2####High');
        $info = $this->question($feedback, 'info', 'AUTOMATIC_TIMESTAMP', '1');
        $foreign = $this->question($other, 'textarea', 'FOREIGN_QUESTION');
        $completed = $this->response(
            $feedback,
            $user->id,
            [$rated->id => '2', $info->id => '1700000000', $foreign->id => 'FOREIGN_ANSWER'],
            true
        );
        $this->response($other, $user->id, [$foreign->id => 'OTHER_RESPONSE']);
        [, $result, $pages] = $this->export($feedback);
        $this->assertCount(2, $pages);
        $response = $pages[$result['filenames'][1]];
        $secrets = ['SECRETNAME', 'PRIVATE', '1700000000', '2023-11-14', 'AUTOMATIC_TIMESTAMP',
            'FOREIGN_ANSWER', 'FOREIGN_QUESTION', 'OTHER_RESPONSE'];
        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $response);
        }
        $this->assertStringContainsString('High (2)', $response);
        $this->assertEquals(0, $DB->get_field('feedback_completed', 'random_response', ['id' => $completed]));
    }

    /**
     * Report access is required even if aggregate statistics are published.
     */
    public function test_report_capability_and_view_capability(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $feedback = $generator->create_module('feedback', ['course' => $course->id, 'publish_stats' => 1]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $question = $this->question($feedback, 'textarea', 'Question');
        $this->response($feedback, $teacher->id, [$question->id => 'PRIVATE_RESPONSE']);
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $context = \context_module::instance($feedback->cmid);
        assign_capability('mod/feedback:viewreports', CAP_PROHIBIT, $role, $context->id);
        $this->setUser($teacher);
        [, , $pages] = $this->export($feedback);
        $this->assertCount(1, $pages);
        $this->assertStringNotContainsString('PRIVATE_RESPONSE', implode('', $pages));
        assign_capability('mod/feedback:view', CAP_PROHIBIT, $role, $context->id);
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        $this->export($feedback);
    }

    /**
     * Separate groups restrict named and anonymous responses to visible users.
     */
    public function test_separate_groups(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['groupmode' => SEPARATEGROUPS, 'groupmodeforce' => 1]);
        $feedback = $generator->create_module('feedback', ['course' => $course->id]);
        $teacher = $generator->create_user();
        $a = $generator->create_user();
        $b = $generator->create_user();
        foreach ([$teacher, $a, $b] as $user) {
            $generator->enrol_user($user->id, $course->id, $user === $teacher ? 'teacher' : 'student');
        }
        $ga = $generator->create_group(['courseid' => $course->id]);
        $gb = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $ga->id, 'userid' => $teacher->id]);
        $generator->create_group_member(['groupid' => $ga->id, 'userid' => $a->id]);
        $generator->create_group_member(['groupid' => $gb->id, 'userid' => $b->id]);
        $role = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $role, \context_course::instance($course->id)->id);
        $question = $this->question($feedback, 'textarea', 'Question');
        $this->response($feedback, $a->id, [$question->id => 'VISIBLE_GROUP']);
        $this->response($feedback, $b->id, [$question->id => 'HIDDEN_GROUP'], true);
        $this->setUser($teacher);
        [, , $pages] = $this->export($feedback);
        $this->assertCount(2, $pages);
        $this->assertStringContainsString('VISIBLE_GROUP', implode('', $pages));
        $this->assertStringNotContainsString('HIDDEN_GROUP', implode('', $pages));
        $this->setAdminUser();
        [, , $pages] = $this->export($feedback);
        $this->assertCount(3, $pages);
    }

    /**
     * Empty feedbacks and unsupported question types have explicit static messages.
     */
    public function test_empty_and_unsupported(): void {
        $course = $this->getDataGenerator()->create_course();
        $feedback = $this->getDataGenerator()->create_module('feedback', ['course' => $course->id]);
        [, , $pages] = $this->export($feedback);
        $this->assertCount(1, $pages);
        $this->assertStringContainsString(get_string('feedbackempty', 'local_flatarchive'), implode('', $pages));
        $this->question($feedback, 'thirdparty', 'Custom question', 'RAW_CONFIGURATION_SECRET');
        [, , $pages] = $this->export($feedback);
        $this->assertStringContainsString(get_string('feedbackunsupported', 'local_flatarchive'), implode('', $pages));
        $this->assertStringNotContainsString('RAW_CONFIGURATION_SECRET', implode('', $pages));
    }
}
