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

use local_flatarchive\archive\resource_exporter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * File export tests with real Moodle storage and ZIP round trips.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\resource_exporter
 */
final class resource_exporter_test extends \advanced_testcase {
    /**
     * Main files, companion files and same-name resources retain their bytes and paths.
     */
    public function test_main_files_and_companions_survive_zip(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $first = $generator->create_module('resource', [
            'course' => $course->id, 'name' => 'Document <test> & file', 'visible' => 0,
        ]);
        $second = $generator->create_module('resource', ['course' => $course->id]);
        $fs = get_file_storage();
        $directory = make_temp_directory('flatarchive-resource-' . bin2hex(random_bytes(8)));
        $expected = [];
        foreach ([$first, $second] as $resource) {
            $context = \context_module::instance($resource->cmid);
            $fs->delete_area_files($context->id, 'mod_resource', 'content');
            // Create a companion first: the exporter must choose by sortorder, not insertion order.
            foreach ([['style.css', 'body { color: blue; }', 0], ['document #1.html', 'File ' . $resource->cmid, 1]] as $data) {
                [$filename, $content, $sortorder] = $data;
                $fs->create_file_from_string([
                    'contextid' => $context->id, 'component' => 'mod_resource', 'filearea' => 'content',
                    'itemid' => 0, 'filepath' => '/nested dir/', 'filename' => $filename, 'sortorder' => $sortorder,
                ], $content);
                $relative = 'files/' . $resource->cmid . '/content/0/nested dir/' . $filename;
                $expected[$relative] = $content;
            }
        }
        $modinfo = get_fast_modinfo($course);
        $items = [];
        foreach ([$second, $first] as $resource) {
            $html = resource_exporter::render($modinfo->get_cm($resource->cmid), $modinfo, $directory);
            $items[$resource->cmid] = $html;
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $anchor = $dom->getElementsByTagName('a')->item(0);
            $this->assertSame(
                'files/' . $resource->cmid . '/content/0/nested%20dir/document%20%231.html',
                $anchor->getAttribute('href')
            );
            $this->assertSame($modinfo->get_cm($resource->cmid)->name, $anchor->textContent);
        }
        $this->assertStringContainsString('fa-vis-hidden', $items[$first->cmid]);
        $sections = local_flatarchive_group_index_links($course, $modinfo, $items);
        $this->assertSame([$items[$first->cmid], $items[$second->cmid]], $sections[0]['links']);
        $zipfiles = [];
        foreach ($expected as $path => $content) {
            $this->assertSame($content, file_get_contents($directory . '/' . $path));
            $zipfiles[$path] = $directory . '/' . $path;
        }
        $packer = get_file_packer('application/zip');
        $this->assertTrue($packer->archive_to_pathname($zipfiles, $directory . '/test.zip'));
        $this->assertNotFalse($packer->extract_to_pathname($directory . '/test.zip', $directory . '/unpacked'));
        foreach ($expected as $path => $content) {
            $this->assertSame($content, file_get_contents($directory . '/unpacked/' . $path));
        }
    }

    /**
     * An empty content area fails explicitly rather than advertising a broken link.
     */
    public function test_missing_file_fails(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $resource = $this->getDataGenerator()->create_module('resource', ['course' => $course->id]);
        get_file_storage()->delete_area_files(\context_module::instance($resource->cmid)->id, 'mod_resource', 'content');
        $modinfo = get_fast_modinfo($course);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('resourcefilenotfound', 'local_flatarchive', $resource->name));
        resource_exporter::render($modinfo->get_cm($resource->cmid), $modinfo, make_temp_directory('resource-empty'));
    }

    /**
     * The adapter rejects another activity type before copying any files.
     */
    public function test_rejects_other_module_types(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $this->expectException(\coding_exception::class);
        resource_exporter::render($modinfo->get_cm($page->cmid), $modinfo, make_temp_directory('resource-invalid'));
    }
}
