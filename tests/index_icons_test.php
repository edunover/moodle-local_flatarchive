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

use local_flatarchive\archive\index_icons;

/**
 * Verify offline icons for every supported activity type.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\index_icons
 */
final class index_icons_test extends \advanced_testcase {
    /**
     * Every activity receives a local icon without altering its content or links.
     */
    public function test_icons_are_included_once_in_zip_and_preserve_content(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $directory = make_request_directory();
        $items = [];
        $types = ['page', 'book', 'label', 'resource', 'folder', 'glossary', 'wiki', 'feedback', 'choice', 'page'];
        foreach ($types as $type) {
            $module = $generator->create_module($type, ['course' => $course->id]);
            $items[$module->cmid] = '<li id="module-' . $module->cmid . '">'
                . '<p><a href="target.html">Été &amp; test</a></p><ul><li>Nested</li></ul></li>';
        }
        $modinfo = get_fast_modinfo($course);
        $result = index_icons::add($items, $modinfo, $directory);
        $this->assertCount(9, $result['files']);
        $this->assertSame(array_keys($items), array_keys($result['items']));
        foreach ($result['items'] as $cmid => $html) {
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new \DOMXPath($dom);
            $icons = $xpath->query('//img[@class="fa-modicon"]');
            $this->assertSame(1, $icons->length);
            $icon = $icons->item(0);
            $path = $icon->getAttribute('src');
            $this->assertStringStartsWith('shared/icons/', $path);
            $this->assertFileExists($result['files'][$path]);
            $this->assertNotSame('', file_get_contents($result['files'][$path]));
            $this->assertSame((string)$modinfo->get_cm($cmid)->get_module_type_name(), $icon->getAttribute('alt'));
            $this->assertSame('module-' . $cmid, $icon->parentNode->getAttribute('id'));
            $this->assertSame('target.html', $xpath->query('//a')->item(0)->getAttribute('href'));
            $this->assertStringContainsString('<ul><li>Nested</li></ul>', $html);
            $this->assertSame($items[$cmid], preg_replace('/<img[^>]+> /', '', $html, 1));
        }
        $packer = get_file_packer('application/zip');
        $zip = $directory . '/icons.zip';
        $this->assertTrue($packer->archive_to_pathname($result['files'], $zip));
        $extracted = $directory . '/extracted';
        $this->assertNotFalse($packer->extract_to_pathname($zip, $extracted));
        foreach ($result['files'] as $path => $source) {
            $this->assertSame(file_get_contents($source), file_get_contents($extracted . '/' . $path));
        }
    }
}
