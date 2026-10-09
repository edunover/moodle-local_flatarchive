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

use local_flatarchive\archive\wiki_exporter;

defined('MOODLE_INTERNAL') || die();

/**
 * Test offline wiki revisions, navigation, files and access isolation.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\wiki_exporter
 */
final class wiki_exporter_test extends \advanced_testcase {
    /** Set up independent wiki fixtures. */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('usecomments', 1);
    }

    /**
     * Export a module and read its generated HTML files.
     *
     * @param \stdClass $course Course.
     * @param \stdClass $wiki Wiki.
     * @return array Export result, directory, and HTML indexed by filename.
     */
    private function export(\stdClass $course, \stdClass $wiki): array {
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('wiki-export-' . bin2hex(random_bytes(8)));
        $result = wiki_exporter::render($modinfo->get_cm($wiki->cmid), $modinfo, $directory);
        $html = [];
        foreach ($result['filenames'] as $filename) {
            $html[$filename] = file_get_contents($directory . '/' . $filename);
        }
        return [$result, $directory, $html];
    }

    /** Latest revisions, Unicode links, fragments, attachments and comments survive a ZIP round trip. */
    public function test_pages_links_files_and_comments(): void {
        global $DB, $CFG, $USER;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $wiki = $generator->create_module('wiki', ['course' => $course->id, 'firstpagetitle' => 'Accueil']);
        $wg = $generator->get_plugin_generator('mod_wiki');
        $second = $wg->create_page($wiki, ['title' => 'Été & suite', 'content' => '<h2 id="details">Suite</h2>']);
        $context = \context_module::instance($wiki->cmid);
        $fileurl = \moodle_url::make_pluginfile_url(
            $context->id, 'mod_wiki', 'attachments', $second->subwikiid, '/', 'image été.png'
        )->out(false);
        $first = $wg->create_first_page($wiki, ['content' => '<p>OLD REVISION</p>']);
        $newcontent = '<p>[[Été & suite]] [[Page absente]]</p><img alt="Test" src="@@PLUGINFILE@@/image%20%C3%A9t%C3%A9.png">' .
            '<a href="' . s($CFG->wwwroot . '/mod/wiki/view.php?pageid=' . $second->id . '#details') . '">Fragment</a>' .
            '<img alt="Absolute" src="' . s($fileurl) . '"><a href="https://example.org/mod/wiki/view.php?pageid=1">External</a>';
        wiki_save_page($first, $newcontent, $USER->id);
        $DB->set_field('wiki_pages', 'cachedcontent', 'STALE CACHE', ['id' => $first->id]);
        foreach (['image été.png' => 'image bytes', 'notes.txt' => 'attachment bytes'] as $name => $bytes) {
            get_file_storage()->create_file_from_string([
                'contextid' => $context->id, 'component' => 'mod_wiki', 'filearea' => 'attachments',
                'itemid' => $first->subwikiid, 'filepath' => '/', 'filename' => $name,
            ], $bytes);
        }
        $DB->insert_record('comments', (object)[
            'contextid' => $context->id, 'component' => 'mod_wiki', 'commentarea' => 'wiki_page',
            'itemid' => $first->id, 'userid' => $USER->id, 'content' => '**Commentaire**',
            'format' => FORMAT_CREOLE, 'timecreated' => 1700000000,
        ]);
        [$result, $directory, $html] = $this->export($course, $wiki);
        $this->assertCount(3, $result['filenames']);
        $firstfile = 'wiki-' . $wiki->cmid . '-page-' . $first->id . '.html';
        $secondfile = 'wiki-' . $wiki->cmid . '-page-' . $second->id . '.html';
        $this->assertStringContainsString($secondfile, $html[$firstfile]);
        $this->assertStringContainsString($secondfile . '#details', $html[$firstfile]);
        $this->assertStringContainsString(get_string('wikinotarchived', 'local_flatarchive'), $html[$firstfile]);
        $this->assertStringContainsString('https://example.org/mod/wiki/view.php?pageid=1', $html[$firstfile]);
        $this->assertStringContainsString('Commentaire', $html[$firstfile]);
        $this->assertStringContainsString('2023-11-14T22:13:20Z', $html[$firstfile]);
        $this->assertStringContainsString(fullname($USER), $html[$firstfile]);
        foreach (['OLD REVISION', 'STALE CACHE', '@@PLUGINFILE@@', '/mod/wiki/create.php', $CFG->wwwroot . '/pluginfile.php'] as $text) {
            $this->assertStringNotContainsString($text, $html[$firstfile]);
        }
        $this->assertSame('STALE CACHE', $DB->get_field('wiki_pages', 'cachedcontent', ['id' => $first->id]));
        $zipfiles = [];
        foreach ($result['filenames'] as $filename) {
            $zipfiles[$filename] = $directory . '/' . $filename;
        }
        foreach (['image été.png' => 'image bytes', 'notes.txt' => 'attachment bytes'] as $name => $bytes) {
            $path = 'files/' . $wiki->cmid . '/attachments/' . $first->subwikiid . '/' . $name;
            $this->assertSame($bytes, file_get_contents($directory . '/' . $path));
            $zipfiles[$path] = $directory . '/' . $path;
        }
        $packer = get_file_packer('application/zip');
        $this->assertTrue($packer->archive_to_pathname($zipfiles, $directory . '/test.zip'));
        $this->assertNotFalse($packer->extract_to_pathname($directory . '/test.zip', $directory . '/unpacked'));
        foreach ($zipfiles as $path => $source) {
            $this->assertSame(file_get_contents($source), file_get_contents($directory . '/unpacked/' . $path));
        }
    }

    /** Creole and NWiki render from source, including their internal page links. */
    public function test_markup_formats(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        foreach (['creole', 'nwiki'] as $format) {
            $wiki = $generator->create_module('wiki', ['course' => $course->id, 'defaultformat' => $format]);
            $wg = $generator->get_plugin_generator('mod_wiki');
            $target = $wg->create_page($wiki, ['title' => 'Destination', 'content' => 'Destination content']);
            $wg->create_first_page($wiki, ['content' => "== Heading ==\n[[Destination]]"]);
            [$result, , $html] = $this->export($course, $wiki);
            $this->assertCount(3, $result['filenames']);
            $all = implode('', $html);
            $this->assertStringContainsString('wiki-' . $wiki->cmid . '-page-' . $target->id . '.html', $all);
            $this->assertStringContainsString('Heading', $all);
            $this->assertStringNotContainsString('[[Destination]]', $all);
        }
    }

    /** Private individual wikis and their files never leak into another student's archive. */
    public function test_individual_visibility(): void {
        global $CFG;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $wiki = $generator->create_module('wiki', ['course' => $course->id, 'wikimode' => 'individual']);
        $owner = $generator->create_user();
        $other = $generator->create_user();
        $generator->enrol_user($owner->id, $course->id, 'student');
        $wg = $generator->get_plugin_generator('mod_wiki');
        $secret = $wg->create_page($wiki, ['userid' => $other->id, 'title' => 'PRIVATE PAGE', 'content' => 'PRIVATE CONTENT']);
        $own = $wg->create_page($wiki, ['userid' => $owner->id, 'content' => '<a href="' .
            $CFG->wwwroot . '/mod/wiki/view.php?pageid=' . $secret->id . '">Unavailable</a>']);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($wiki->cmid)->id, 'component' => 'mod_wiki',
            'filearea' => 'attachments', 'itemid' => $secret->subwikiid, 'filepath' => '/', 'filename' => 'secret.txt',
        ], 'private bytes');
        $this->setUser($owner);
        [$result, $directory, $html] = $this->export($course, $wiki);
        $this->assertCount(2, $result['filenames']);
        $this->assertStringNotContainsString('PRIVATE', implode('', $html));
        $this->assertDirectoryDoesNotExist($directory . '/files/' . $wiki->cmid . '/attachments/' . $secret->subwikiid);
        $this->assertStringContainsString(get_string('wikinotarchived', 'local_flatarchive'), implode('', $html));
        $this->setAdminUser();
        [$result] = $this->export($course, $wiki);
        $this->assertCount(3, $result['filenames']);
    }

    /** Separate groups remain isolated; identical page titles have separate destinations. */
    public function test_group_visibility_and_collisions(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['groupmode' => SEPARATEGROUPS]);
        $wiki = $generator->create_module('wiki', ['course' => $course->id, 'groupmode' => SEPARATEGROUPS]);
        $owner = $generator->create_user();
        $generator->enrol_user($owner->id, $course->id, 'student');
        $group = $generator->create_group(['courseid' => $course->id, 'name' => 'Group A']);
        $other = $generator->create_group(['courseid' => $course->id, 'name' => 'Group B']);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $owner->id]);
        $wg = $generator->get_plugin_generator('mod_wiki');
        $wg->create_page($wiki, ['group' => $group->id, 'title' => 'Same title', 'content' => 'GROUP A']);
        $wg->create_page($wiki, ['group' => $other->id, 'title' => 'Same title', 'content' => 'GROUP B']);
        $this->setUser($owner);
        [$result, , $html] = $this->export($course, $wiki);
        $this->assertCount(2, $result['filenames']);
        $this->assertStringContainsString('GROUP A', implode('', $html));
        $this->assertStringNotContainsString('GROUP B', implode('', $html));
        $this->setAdminUser();
        [$result, , $html] = $this->export($course, $wiki);
        $this->assertCount(3, array_unique($result['filenames']));
        $this->assertStringContainsString('GROUP B', implode('', $html));
    }

    /** Denied comments are excluded, while an empty wiki still produces a useful index. */
    public function test_comments_permissions_and_empty_wiki(): void {
        global $DB, $USER;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $wiki = $generator->create_module('wiki', ['course' => $course->id]);
        [$result, , $html] = $this->export($course, $wiki);
        $this->assertCount(1, $result['filenames']);
        $this->assertStringContainsString(get_string('wikiempty', 'local_flatarchive'), implode('', $html));
        $page = $generator->get_plugin_generator('mod_wiki')->create_first_page($wiki);
        $context = \context_module::instance($wiki->cmid);
        $DB->insert_record('comments', (object)[
            'contextid' => $context->id, 'component' => 'mod_wiki', 'commentarea' => 'wiki_page',
            'itemid' => $page->id, 'userid' => $USER->id, 'content' => 'PRIVATE COMMENT',
            'format' => FORMAT_HTML, 'timecreated' => time(),
        ]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        assign_capability('mod/wiki:viewcomment', CAP_PROHIBIT, $role, $context->id);
        $this->setUser($teacher);
        [, , $html] = $this->export($course, $wiki);
        $this->assertStringNotContainsString('PRIVATE COMMENT', implode('', $html));
        assign_capability('mod/wiki:viewpage', CAP_PROHIBIT, $role, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->expectException(\required_capability_exception::class);
        $this->export($course, $wiki);
    }
}
