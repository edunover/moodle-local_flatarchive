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

use local_flatarchive\archive\folder_exporter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Folder exports preserve hierarchy, escaped links and original bytes in ZIPs.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\folder_exporter
 */
final class folder_exporter_test extends \advanced_testcase {
    /**
     * Same-name folders and files remain isolated, including nested and empty directories.
     */
    public function test_hierarchy_and_assets_survive_zip(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $fs = get_file_storage();
        $directory = make_temp_directory('flatarchive-folder-' . bin2hex(random_bytes(8)));
        $expected = [];
        $exports = [];
        foreach ([0, 1] as $visible) {
            $folder = $generator->create_module('folder', [
                'course' => $course->id, 'name' => 'Documents <été> & notes', 'visible' => $visible,
                'intro' => '<p>Description <img src="@@PLUGINFILE@@/cover.png" alt="Cover"></p>',
                'introformat' => FORMAT_HTML,
            ]);
            $context = \context_module::instance($folder->cmid);
            $fs->delete_area_files($context->id, 'mod_folder', 'content');
            foreach (['/', '/été # notes/', '/été # notes/sub/'] as $path) {
                $content = 'Bytes ' . $folder->cmid . $path;
                $fs->create_file_from_string([
                    'contextid' => $context->id, 'component' => 'mod_folder', 'filearea' => 'content',
                    'itemid' => 0, 'filepath' => $path, 'filename' => 'document #1.txt',
                ], $content);
                $expected['files/' . $folder->cmid . '/content/0' . $path . 'document #1.txt'] = $content;
            }
            // A real index.html must never be replaced by the generated folder navigation.
            $fs->create_file_from_string([
                'contextid' => $context->id, 'component' => 'mod_folder', 'filearea' => 'content',
                'itemid' => 0, 'filepath' => '/', 'filename' => 'index.html',
            ], 'Original index');
            $expected['files/' . $folder->cmid . '/content/0/index.html'] = 'Original index';
            $fs->create_directory($context->id, 'mod_folder', 'content', 0, '/Empty/');
            $image = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aL1sAAAAASUVORK5CYII='
            );
            $fs->create_file_from_string([
                'contextid' => $context->id, 'component' => 'mod_folder', 'filearea' => 'intro',
                'itemid' => 0, 'filepath' => '/', 'filename' => 'cover.png',
            ], $image);
            $expected['files/' . $folder->cmid . '/intro/0/cover.png'] = $image;
            $modinfo = get_fast_modinfo($course);
            $export = folder_exporter::render($modinfo->get_cm($folder->cmid), $modinfo, $directory);
            $exports[] = $export;
            $html = file_get_contents($directory . '/' . $export['filename']);
            $expected[$export['filename']] = $html;
            $this->assertStringContainsString('Documents &lt;été&gt; &amp; notes', $html);
            $this->assertStringContainsString('Description', $html);
            $this->assertStringContainsString(get_string('folderempty', 'local_flatarchive'), $html);
            $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//li[strong="été # notes"]/ul/li[strong="sub"]/ul/li/a')->length);
            foreach ($xpath->query('//a[starts-with(@href,"files/")] | //img') as $node) {
                $url = $node->getAttribute($node->nodeName === 'a' ? 'href' : 'src');
                $this->assertArrayHasKey(rawurldecode($url), $expected);
                $this->assertFileExists($directory . '/' . rawurldecode($url));
                $this->assertStringNotContainsString(' ', $url);
            }
            $this->assertStringContainsString('href="index.html"', $html);
            $this->assertStringContainsString('href="' . $export['filename'] . '"', $export['item']);
            if (!$visible) {
                $this->assertStringContainsString('fa-vis-hidden', $html);
                $this->assertStringContainsString('fa-vis-hidden', $export['item']);
            }
        }
        $this->assertNotSame($exports[0]['filename'], $exports[1]['filename']);
        $zipfiles = [];
        foreach ($expected as $path => $bytes) {
            $this->assertSame($bytes, file_get_contents($directory . '/' . $path));
            $zipfiles[$path] = $directory . '/' . $path;
        }
        $packer = get_file_packer('application/zip');
        $this->assertTrue($packer->archive_to_pathname($zipfiles, $directory . '/test.zip'));
        $this->assertNotFalse($packer->extract_to_pathname($directory . '/test.zip', $directory . '/unpacked'));
        foreach ($expected as $path => $bytes) {
            $this->assertSame($bytes, file_get_contents($directory . '/unpacked/' . $path));
        }
    }

    /**
     * Empty folders still produce a valid page and retain plain-text descriptions safely.
     */
    public function test_empty_folder_and_plain_text_intro(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $folder = $this->getDataGenerator()->create_module('folder', [
            'course' => $course->id, 'intro' => '<b>Plain text</b>', 'introformat' => FORMAT_PLAIN,
        ]);
        get_file_storage()->delete_area_files(\context_module::instance($folder->cmid)->id, 'mod_folder', 'content');
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('flatarchive-folder-empty');
        $export = folder_exporter::render($modinfo->get_cm($folder->cmid), $modinfo, $directory);
        $html = file_get_contents($directory . '/' . $export['filename']);
        $this->assertStringContainsString(get_string('folderempty', 'local_flatarchive'), $html);
        $this->assertStringContainsString('&lt;b&gt;Plain text&lt;/b&gt;', $html);
    }

    /**
     * Reject unsupported module types before accessing a folder instance.
     */
    public function test_rejects_other_module_types(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $this->expectException(\coding_exception::class);
        folder_exporter::render($modinfo->get_cm($page->cmid), $modinfo, make_temp_directory('folder-invalid'));
    }
}
