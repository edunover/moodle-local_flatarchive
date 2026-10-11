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

use local_flatarchive\archive\forum_exporter;

/**
 * Forum files, navigation and native visibility restrictions.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\forum_exporter
 */
final class forum_exporter_test extends \advanced_testcase {
    /**
     * Reset fixture data.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Create a discussion and read its complete record.
     *
     * @param \stdClass $forum Forum fixture.
     * @param int $userid Author.
     * @param array $extra Overrides.
     * @return \stdClass Discussion record.
     */
    private function discussion(\stdClass $forum, int $userid, array $extra = []): \stdClass {
        global $DB;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion(
            $extra + ['course' => $forum->course, 'forum' => $forum->id, 'userid' => $userid]
        );
        return $DB->get_record('forum_discussions', ['id' => $discussion->id], '*', MUST_EXIST);
    }

    /**
     * Export and collect the generated pages.
     *
     * @param \stdClass $forum Forum fixture.
     * @return array Directory, concatenated HTML and export metadata.
     */
    private function export(\stdClass $forum): array {
        $info = get_fast_modinfo($forum->course);
        $directory = make_request_directory();
        $export = forum_exporter::render($info->get_cm($forum->cmid), $info, $directory);
        $html = '';
        foreach ($export['filenames'] as $filename) {
            $html .= file_get_contents($directory . '/' . $filename);
        }
        return [$directory, $html, $export];
    }

    /**
     * Preserve media bytes, attachments and parent navigation without marking messages read.
     */
    public function test_content_files_and_navigation(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forum = $generator->create_module('forum', ['course' => $course->id]);
        $user = $generator->create_user(['firstname' => 'ForumAuthor', 'lastname' => 'Example']);
        $discussion = $this->discussion($forum, $user->id, ['name' => 'Été & discussion']);
        $post = $generator->get_plugin_generator('mod_forum')->create_post([
            'discussion' => $discussion->id, 'userid' => $user->id, 'parent' => $discussion->firstpost,
            'message' => '<p>Offline message</p><img src="@@PLUGINFILE@@/été.png" alt="Image">',
            'messageformat' => FORMAT_HTML, 'created' => 1700000000, 'modified' => 1700000100,
        ]);
        $context = \context_module::instance($forum->cmid);
        foreach (['post' => 'été.png', 'attachment' => 'document été.txt'] as $area => $filename) {
            get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'mod_forum',
                'filearea' => $area, 'itemid' => $post->id, 'filepath' => '/', 'filename' => $filename], 'Original bytes');
        }
        $before = $DB->count_records('forum_read');
        [$directory, $html, $export] = $this->export($forum);
        $this->assertCount(2, $export['filenames']);
        $this->assertStringContainsString('Été &amp; discussion', $html);
        $this->assertStringContainsString('ForumAuthor Example', $html);
        $this->assertStringContainsString('href="#post-' . $discussion->firstpost . '"', $html);
        $this->assertStringContainsString('id="post-' . $post->id . '"', $html);
        $this->assertStringContainsString('document%20%C3%A9t%C3%A9.txt', $html);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertSame($before, $DB->count_records('forum_read'));
        $path = '/files/' . $forum->cmid . '/';
        $this->assertSame('Original bytes', file_get_contents($directory . $path . 'post/' . $post->id . '/été.png'));
        $attachment = $path . 'attachment/' . $post->id . '/document été.txt';
        $this->assertSame('Original bytes', file_get_contents($directory . $attachment));
        $packer = get_file_packer('application/zip');
        $zip = $directory . '/test.zip';
        $this->assertTrue($packer->archive_to_pathname([ltrim($attachment, '/') => $directory . $attachment], $zip));
        $this->assertNotFalse($packer->extract_to_pathname($zip, $directory . '/unzipped'));
        $this->assertSame('Original bytes', file_get_contents($directory . '/unzipped' . $attachment));
    }

    /**
     * Pinned markers and parent-first ordering distinguish nested replies from siblings.
     */
    public function test_pinned_and_nested_replies(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forum = $generator->create_module('forum', ['course' => $course->id]);
        $discussion = $this->discussion($forum, 2, ['pinned' => 1, 'name' => 'Pinned topic']);
        $this->discussion($forum, 2, ['pinned' => 0, 'name' => 'Normal topic']);
        $posts = $generator->get_plugin_generator('mod_forum');
        $reply = $posts->create_post(['discussion' => $discussion->id, 'userid' => 2,
            'parent' => $discussion->firstpost, 'created' => 1700000000]);
        $sibling = $posts->create_post(['discussion' => $discussion->id, 'userid' => 2,
            'parent' => $discussion->firstpost, 'created' => 1700000001]);
        $nested = $posts->create_post(['discussion' => $discussion->id, 'userid' => 2,
            'parent' => $reply->id, 'created' => 1700000002]);
        [$directory, $html, $export] = $this->export($forum);
        $this->assertSame(2, substr_count($html, 'class="fa-forum-pinned"'));
        $this->assertStringContainsString(get_string('forumpinned', 'local_flatarchive'), $html);
        $page = file_get_contents($directory . '/forum-' . $forum->cmid . '-discussion-' . $discussion->id . '.html');
        $dom = new \DOMDocument();
        @$dom->loadHTML($page);
        $xpath = new \DOMXPath($dom);
        $articles = $xpath->query('//article[@class="fa-forum-post"]');
        $ids = [];
        $depths = [];
        foreach ($articles as $article) {
            $ids[] = $article->getAttribute('id');
            $depths[] = $article->getAttribute('data-depth');
        }
        $this->assertSame(['post-' . $discussion->firstpost, 'post-' . $reply->id,
            'post-' . $nested->id, 'post-' . $sibling->id], $ids);
        $this->assertSame(['0', '1', '2', '1'], $depths);
        $this->assertSame(
            '#post-' . $reply->id,
            $xpath->query('//article[@id="post-' . $nested->id . '"]/p/a')->item(0)->getAttribute('href')
        );
    }

    /**
     * A hidden parent is not reconstructed; malformed cycles cannot duplicate or lose messages.
     */
    public function test_missing_parent_and_cycle(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forum = $generator->create_module('forum', ['course' => $course->id]);
        $discussion = $this->discussion($forum, 2);
        $posts = $generator->get_plugin_generator('mod_forum');
        $hidden = $posts->create_post(['discussion' => $discussion->id, 'userid' => 2,
            'parent' => $discussion->firstpost, 'deleted' => 1, 'subject' => 'HiddenParentSubject']);
        $orphan = $posts->create_post(['discussion' => $discussion->id, 'userid' => 2, 'parent' => $hidden->id]);
        $a = $posts->create_post(['discussion' => $discussion->id, 'userid' => 2]);
        $b = $posts->create_post(['discussion' => $discussion->id, 'userid' => 2, 'parent' => $a->id]);
        $DB->set_field('forum_posts', 'parent', $b->id, ['id' => $a->id]);
        [, $html] = $this->export($forum);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertStringNotContainsString('HiddenParentSubject', $html);
        $this->assertStringNotContainsString('href="#post-' . $hidden->id . '"', $html);
        $this->assertSame('0', $xpath->query('//article[@id="post-' . $orphan->id . '"]')->item(0)
            ->getAttribute('data-depth'));
        foreach ([$a, $b, $orphan] as $post) {
            $this->assertSame(1, $xpath->query('//article[@id="post-' . $post->id . '"]')->length);
        }
    }

    /**
     * Invisible group discussions, scheduled discussions and deleted posts export no files.
     */
    public function test_group_timing_and_deleted_restrictions(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['groupmode' => SEPARATEGROUPS]);
        $forum = $generator->create_module('forum', ['course' => $course->id, 'groupmode' => SEPARATEGROUPS]);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $group = $generator->create_group(['courseid' => $course->id]);
        $this->discussion($forum, 2, ['groupid' => $group->id, 'name' => 'OtherGroupSecret']);
        set_config('forum_enabletimedposts', 1);
        $this->discussion($forum, 2, ['timestart' => time() + 3600, 'name' => 'FutureSecret']);
        $discussion = $this->discussion($forum, 2, ['name' => 'PublicDiscussion']);
        $post = $generator->get_plugin_generator('mod_forum')->create_post([
            'discussion' => $discussion->id, 'userid' => 2, 'parent' => $discussion->firstpost,
            'deleted' => 1, 'message' => 'DeletedSecret',
        ]);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($forum->cmid)->id, 'component' => 'mod_forum',
            'filearea' => 'attachment', 'itemid' => $post->id, 'filepath' => '/', 'filename' => 'secret.txt',
        ], 'Secret bytes');
        $this->setUser($student);
        [$directory, $html] = $this->export($forum);
        $this->assertStringContainsString('PublicDiscussion', $html);
        foreach (['OtherGroupSecret', 'FutureSecret', 'DeletedSecret', 'secret.txt'] as $secret) {
            $this->assertStringNotContainsString($secret, $html);
        }
        $this->assertDirectoryDoesNotExist($directory . '/files/' . $forum->cmid . '/attachment/' . $post->id);
    }

    /**
     * Q and A answers stay hidden until the user's editing period has elapsed.
     */
    public function test_questions_and_answers(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forum = $generator->create_module('forum', ['course' => $course->id, 'type' => 'qanda']);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $discussion = $this->discussion($forum, 2);
        $generator->get_plugin_generator('mod_forum')->create_post([
            'discussion' => $discussion->id, 'userid' => 2, 'parent' => $discussion->firstpost,
            'message' => 'HiddenAnswerMarker',
        ]);
        $this->setUser($student);
        $this->assertStringNotContainsString('HiddenAnswerMarker', $this->export($forum)[1]);
        $generator->get_plugin_generator('mod_forum')->create_post([
            'discussion' => $discussion->id, 'userid' => $student->id, 'parent' => $discussion->firstpost,
            'created' => time() - 86400, 'message' => 'My answer',
        ]);
        $this->assertStringContainsString('HiddenAnswerMarker', $this->export($forum)[1]);
    }

    /**
     * A private reply is visible to its recipient but not to a different student.
     */
    public function test_private_replies(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forum = $generator->create_module('forum', ['course' => $course->id]);
        $recipient = $generator->create_user();
        $other = $generator->create_user();
        foreach ([$recipient, $other] as $user) {
            $generator->enrol_user($user->id, $course->id, 'student');
        }
        $discussion = $this->discussion($forum, 2);
        $generator->get_plugin_generator('mod_forum')->create_post([
            'discussion' => $discussion->id, 'userid' => 2, 'parent' => $discussion->firstpost,
            'privatereplyto' => $recipient->id, 'message' => 'PrivateReplyMarker',
        ]);
        $this->setUser($other);
        $this->assertStringNotContainsString('PrivateReplyMarker', $this->export($forum)[1]);
        $this->setUser($recipient);
        $this->assertStringContainsString('PrivateReplyMarker', $this->export($forum)[1]);
    }

    /**
     * A single discussion hides its opening author; view capability remains mandatory.
     */
    public function test_single_discussion_author_and_access(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user(['firstname' => 'HiddenOpeningAuthor']);
        $generator->enrol_user($user->id, $course->id, 'editingteacher');
        $this->setUser($user);
        $forum = $generator->create_module('forum', ['course' => $course->id, 'type' => 'single']);
        $this->assertStringNotContainsString('HiddenOpeningAuthor', $this->export($forum)[1]);
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('mod/forum:viewdiscussion', CAP_PROHIBIT, $role, \context_module::instance($forum->cmid)->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->expectException(\required_capability_exception::class);
        $this->export($forum);
    }
}
