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

use local_flatarchive\archive\label_exporter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
require_once($CFG->dirroot . '/local/flatarchive/archive_templates/index_template.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Offline text and media export tests using real Moodle records and files.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\label_exporter
 */
final class label_exporter_test extends \advanced_testcase {
    /**
     * Inline content keeps course order and isolated attachments survive ZIP extraction.
     */
    public function test_inline_content_and_attachments(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'topics', 'numsections' => 1]);
        $first = $generator->create_module('label', [
            'course' => $course->id, 'section' => 1, 'visible' => 0,
            'intro' => '<p>Before the page</p><img src="@@PLUGINFILE@@/nested/image%20one.png" alt="First">',
            'introformat' => FORMAT_HTML,
        ]);
        $page = $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $second = $generator->create_module('label', [
            'course' => $course->id, 'section' => 1,
            'intro' => '<p>After the page</p><img src="@@PLUGINFILE@@/nested/image%20one.png" alt="Second">',
            'introformat' => FORMAT_HTML,
        ]);
        $generator->create_module('label', ['course' => $course->id, 'section' => 1, 'intro' => 'Unselected']);
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('flatarchive-label-' . bin2hex(random_bytes(8)));
        $zipfiles = [];
        $items = [];
        foreach ([$second, $first] as $label) {
            $relative = 'files/' . $label->cmid . '/intro/0/nested/image one.png';
            get_file_storage()->create_file_from_string([
                'contextid' => \context_module::instance($label->cmid)->id,
                'component' => 'mod_label', 'filearea' => 'intro', 'itemid' => 0,
                'filepath' => '/nested/', 'filename' => 'image one.png',
            ], 'Image for ' . $label->cmid);
            $items[$label->cmid] = label_exporter::render($modinfo->get_cm($label->cmid), $modinfo, $directory);
            $this->assertStringContainsString(str_replace(' ', '%20', $relative), $items[$label->cmid]);
            $zipfiles[$relative] = $directory . '/' . $relative;
        }
        $items[$page->cmid] = '<li><a href="page.html">Page</a></li>';
        $sections = local_flatarchive_group_index_links($course, $modinfo, $items);
        $html = local_flatarchive_render_index_template($course, $course->id, $sections, $course->shortname);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $entries = $xpath->query('//section/ul/li');
        $this->assertSame(3, $entries->length);
        $this->assertSame('module-' . $first->cmid, $entries->item(0)->getAttribute('id'));
        $this->assertSame('Page', $entries->item(1)->textContent);
        $this->assertSame('module-' . $second->cmid, $entries->item(2)->getAttribute('id'));
        $this->assertSame(2, $xpath->query('//li[@class="fa-textmedia"]//img')->length);
        $this->assertSame(0, $xpath->query('//li[@class="fa-textmedia"]/a')->length);
        $this->assertStringContainsString('fa-vis-hidden', $items[$first->cmid]);
        $this->assertStringNotContainsString('Unselected', $html);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
        $this->assertStringNotContainsString('pluginfile.php', $html);
        local_flatarchive_write_file($directory . '/index.html', $html);
        $zipfiles['index.html'] = $directory . '/index.html';
        $packer = get_file_packer('application/zip');
        $this->assertTrue($packer->archive_to_pathname($zipfiles, $directory . '/test.zip'));
        $this->assertNotFalse($packer->extract_to_pathname($directory . '/test.zip', $directory . '/unpacked'));
        foreach ([$first, $second] as $label) {
            $file = $directory . '/unpacked/files/' . $label->cmid . '/intro/0/nested/image one.png';
            $this->assertSame('Image for ' . $label->cmid, file_get_contents($file));
        }
    }

    /**
     * The stored format is honoured; plain text must not become executable HTML.
     */
    public function test_plain_text_format(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $label = $this->getDataGenerator()->create_module('label', [
            'course' => $course->id, 'intro' => '<strong>Literal text</strong>', 'introformat' => FORMAT_PLAIN,
        ]);
        $modinfo = get_fast_modinfo($course);
        $html = label_exporter::render($modinfo->get_cm($label->cmid), $modinfo, make_temp_directory('label-plain'));
        $this->assertStringContainsString('&lt;strong&gt;Literal text&lt;/strong&gt;', $html);
        $this->assertStringNotContainsString('<strong>', $html);
    }

    /**
     * Programming errors cannot export another activity through the label adapter.
     */
    public function test_rejects_other_module_types(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $this->expectException(\coding_exception::class);
        label_exporter::render($modinfo->get_cm($page->cmid), $modinfo, make_temp_directory('label-invalid'));
    }
}
