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

use local_flatarchive\archive\glossary_exporter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Glossary export content, isolation and original attachment bytes.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\glossary_exporter
 */
final class glossary_exporter_test extends \advanced_testcase {
    /**
     * Create independent data for every test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Create an entry without depending on version-specific generator options.
     *
     * @param int $glossaryid Glossary ID.
     * @param array $overrides Entry overrides.
     * @return int Entry ID.
     */
    private function entry(int $glossaryid, array $overrides = []): int {
        global $DB, $USER;
        return $DB->insert_record('glossary_entries', (object)array_merge([
            'glossaryid' => $glossaryid, 'userid' => $USER->id, 'concept' => 'Été <test>',
            'definition' => '<p>Definition <img src="@@PLUGINFILE@@/image.png" alt="Image"></p>',
            'definitionformat' => FORMAT_HTML, 'attachment' => '1', 'approved' => 1,
            'timecreated' => time(), 'timemodified' => time(),
        ], $overrides));
    }

    /**
     * Copy fixtures to an entry file area.
     *
     * @param int $cmid Storage module.
     * @param int $entryid Entry ID.
     * @param string $area File area.
     * @param string $filename Filename.
     * @param string $bytes Original contents.
     */
    private function file(int $cmid, int $entryid, string $area, string $filename, string $bytes): void {
        get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($cmid)->id, 'component' => 'mod_glossary',
            'filearea' => $area, 'itemid' => $entryid, 'filepath' => '/', 'filename' => $filename,
        ], $bytes);
    }

    /**
     * Duplicate concepts, aliases, categories, formats and files survive a ZIP round trip.
     */
    public function test_entries_and_files_survive_zip(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $glossary = $generator->create_module('glossary', ['course' => $course->id, 'visible' => 0]);
        $other = $generator->create_module('glossary', ['course' => $course->id]);
        $this->entry($other->id, ['concept' => 'FOREIGN ENTRY']);
        $pending = $this->entry($glossary->id, ['concept' => 'PENDING SECRET', 'approved' => 0]);
        $this->file($glossary->cmid, $pending, 'attachment', 'secret.txt', 'private');
        $first = $this->entry($glossary->id);
        $second = $this->entry($glossary->id, ['definition' => '<b>Plain text</b>', 'definitionformat' => FORMAT_PLAIN]);
        $DB->insert_record('glossary_alias', ['entryid' => $first, 'alias' => 'Synonyme <test>']);
        $category = $DB->insert_record('glossary_categories', ['glossaryid' => $glossary->id, 'name' => 'Catégorie & été']);
        $DB->insert_record('glossary_entries_categories', ['entryid' => $first, 'categoryid' => $category]);
        $expected = [];
        foreach ([$first, $second] as $id) {
            $this->file($glossary->cmid, $id, 'attachment', 'notes été.txt', 'Entry ' . $id);
            $expected['files/' . $glossary->cmid . '/attachment/' . $id . '/notes été.txt'] = 'Entry ' . $id;
        }
        $this->file($glossary->cmid, $first, 'entry', 'image.png', 'image fixture');
        $expected['files/' . $glossary->cmid . '/entry/' . $first . '/image.png'] = 'image fixture';
        $directory = make_temp_directory('glossary-test-' . bin2hex(random_bytes(8)));
        $modinfo = get_fast_modinfo($course);
        $result = glossary_exporter::render($modinfo->get_cm($glossary->cmid), $modinfo, $directory);
        $html = file_get_contents($directory . '/' . $result['filename']);
        $this->assertStringContainsString(get_string('glossaryexcluded', 'local_flatarchive', 1), $html);
        $this->assertStringNotContainsString('Definition', $html);
        $this->assertDirectoryDoesNotExist($directory . '/files/' . $glossary->cmid . '/attachment/' . $pending);
        $this->assertCount(3, $result['filenames']);
        $zipfiles = [];
        $allhtml = $html;
        foreach ($result['filenames'] as $filename) {
            $zipfiles[$filename] = $directory . '/' . $filename;
        }
        foreach ([$first, $second] as $id) {
            $entryfile = 'glossary-' . $glossary->cmid . '-entry-' . $id . '.html';
            $this->assertContains($entryfile, $result['filenames']);
            $this->assertStringContainsString('href="' . $entryfile . '"', $html);
            $entryhtml = file_get_contents($directory . '/' . $entryfile);
            $allhtml .= $entryhtml;
            $dom = new \DOMDocument();
            @$dom->loadHTML($entryhtml);
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//h1')->length);
            $this->assertSame('Été <test>', $xpath->query('//h1')->item(0)->textContent);
            $this->assertSame(1, $xpath->query('//a[@href="' . $result['filename'] . '"]')->length);
            $this->assertStringContainsString(get_string('backtoglossary', 'local_flatarchive'), $entryhtml);
            foreach ($xpath->query('//a[starts-with(@href,"files/")] | //img') as $node) {
                $url = $node->getAttribute($node->nodeName === 'a' ? 'href' : 'src');
                $this->assertArrayHasKey(rawurldecode($url), $expected);
            }
        }
        foreach (['Synonyme &lt;test&gt;', 'Catégorie &amp; été', '&lt;b&gt;Plain text&lt;/b&gt;', 'fa-vis-hidden'] as $text) {
            $this->assertStringContainsString($text, $allhtml);
        }
        foreach (['PENDING SECRET', 'FOREIGN ENTRY', 'secret.txt', '@@PLUGINFILE@@'] as $text) {
            $this->assertStringNotContainsString($text, $allhtml);
        }
        foreach ($expected as $path => $bytes) {
            $this->assertSame($bytes, file_get_contents($directory . '/' . $path));
            $zipfiles[$path] = $directory . '/' . $path;
        }
        $packer = get_file_packer('application/zip');
        $this->assertTrue($packer->archive_to_pathname($zipfiles, $directory . '/test.zip'));
        $this->assertNotFalse($packer->extract_to_pathname($directory . '/test.zip', $directory . '/unpacked'));
        foreach ($result['filenames'] as $filename) {
            $this->assertSame(
                file_get_contents($directory . '/' . $filename),
                file_get_contents($directory . '/unpacked/' . $filename)
            );
        }
        foreach ($expected as $path => $bytes) {
            $this->assertSame($bytes, file_get_contents($directory . '/unpacked/' . $path));
        }
    }

    /**
     * Entries exported to a main glossary keep their files when archiving the source glossary.
     */
    public function test_source_glossary_uses_main_file_context(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $source = $generator->create_module('glossary', ['course' => $course->id]);
        $main = $generator->create_module('glossary', ['course' => $course->id, 'mainglossary' => 1]);
        $id = $this->entry($main->id, ['sourceglossaryid' => $source->id]);
        $this->file($main->cmid, $id, 'entry', 'image.png', 'moved image');
        $this->file($main->cmid, $id, 'attachment', 'same.txt', 'moved attachment');
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('glossary-source');
        foreach ([$source, $main] as $module) {
            $result = glossary_exporter::render($modinfo->get_cm($module->cmid), $modinfo, $directory);
            $this->assertStringContainsString('entry-' . $id, file_get_contents($directory . '/' . $result['filename']));
            $base = $directory . '/files/' . $module->cmid;
            $this->assertSame('moved image', file_get_contents($base . '/entry/' . $id . '/image.png'));
            $this->assertSame('moved attachment', file_get_contents($base . '/attachment/' . $id . '/same.txt'));
        }
    }

    /**
     * Empty glossaries retain their description and introduction attachments.
     */
    public function test_empty_glossary_with_intro(): void {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('glossary', [
            'course' => $course->id, 'intro' => '<p>Introduction <img src="@@PLUGINFILE@@/image.png" alt="Intro"></p>',
            'introformat' => FORMAT_HTML,
        ]);
        $this->file($module->cmid, 0, 'intro', 'image.png', 'intro image');
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('glossary-empty');
        $result = glossary_exporter::render($modinfo->get_cm($module->cmid), $modinfo, $directory);
        $html = file_get_contents($directory . '/' . $result['filename']);
        $this->assertStringContainsString(get_string('glossaryempty', 'local_flatarchive'), $html);
        $this->assertStringContainsString('Introduction', $html);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
        $this->assertSame('intro image', file_get_contents($directory . '/files/' . $module->cmid . '/intro/0/image.png'));
    }

    /**
     * Reject modules that are not glossaries.
     */
    public function test_rejects_other_module_types(): void {
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $this->expectException(\coding_exception::class);
        glossary_exporter::render($modinfo->get_cm($page->cmid), $modinfo, make_temp_directory('glossary-invalid'));
    }
}
