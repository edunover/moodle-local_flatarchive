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

use local_flatarchive\archive\appearance;
use local_flatarchive\archive\layout;

/**
 * Offline institution styles and generated-page hooks.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\appearance
 */
final class appearance_test extends \advanced_testcase {
    /**
     * Custom CSS remains literal and follows the base CSS on root and nested pages, not attachments.
     */
    public function test_offline_styles_and_hooks(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        $dir = make_request_directory();
        mkdir($dir . '/shared');
        mkdir($dir . '/book');
        mkdir($dir . '/files');
        $css = '/* </style><script>never HTML</script> */ .flatarchive-title {color: #123456;}';
        set_config('customcss', $css, 'local_flatarchive');
        $files = appearance::write($dir);
        $html = '<!doctype html><html><head><link rel="stylesheet" href="shared/moodle.css"></head>' .
            '<body><main><h1>Title</h1><div class="card"><p>Text <a href="index.html">Home</a></p>' .
            '<table class="fa-settings-table"><tr><th>A</th><td>B</td></tr></table></div></main></body></html>';
        foreach (
            ['index.html' => $html, 'book/chapter.html' => str_replace('shared/', '../shared/', $html),
            'files/original.html' => $html, 'shared/moodle.css' => 'body {color: black;}'] as $name => $content
        ) {
            file_put_contents($dir . '/' . $name, $content);
            $files[$name] = $dir . '/' . $name;
        }
        $mapped = layout::organise($files);
        $this->assertSame($css, file_get_contents($mapped['resources/shared/institution.css']));
        $this->assertSame($html, file_get_contents($mapped['resources/files/original.html']));
        foreach (['index.html' => 'resources/shared/', 'resources/book/chapter.html' => '../shared/'] as $name => $prefix) {
            $dom = new \DOMDocument();
            @$dom->loadHTML(file_get_contents($mapped[$name]));
            $xpath = new \DOMXPath($dom);
            $links = $xpath->query('//link[@rel="stylesheet"]');
            $this->assertSame($prefix . 'institution.css', $links->item($links->length - 1)->getAttribute('href'));
            foreach ($xpath->query('//body | //body//*') as $node) {
                $this->assertContains('flatarchive-' . $node->nodeName, explode(' ', $node->getAttribute('class')));
            }
            $this->assertStringContainsString('flatarchive-settings-table', $dom->saveHTML());
            $this->assertSame(0, $xpath->query('//script')->length);
        }
    }

    /**
     * None means no override; all three starter styles are local and editable.
     */
    public function test_presets_and_empty_override(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        $presets = appearance::presets();
        $this->assertSame(['none', 'institutional', 'modern', 'classic'], array_keys($presets));
        $this->assertSame('', $presets['none']);
        foreach (array_slice($presets, 1) as $css) {
            $this->assertStringContainsString('.flatarchive-title', $css);
            $this->assertStringNotContainsString('http', $css);
            $this->assertStringNotContainsString('@import', $css);
        }
        $dir = make_request_directory();
        mkdir($dir . '/shared');
        $files = appearance::write($dir);
        $this->assertSame('', file_get_contents($files['shared/institution.css']));
    }
}
