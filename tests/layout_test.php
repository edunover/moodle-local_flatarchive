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

use local_flatarchive\archive\layout;

/**
 * Root entry point, nested navigation and unchanged original files.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\layout
 */
final class layout_test extends \advanced_testcase {
    /**
     * Reorganised archives remain navigable after real ZIP extraction.
     */
    public function test_portable_links_and_original_files(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->libdir . '/filelib.php');
        $this->resetAfterTest();
        $directory = make_temp_directory('flatarchive-layout-' . bin2hex(random_bytes(8)));
        $index = '<!DOCTYPE html><html><head><meta charset="UTF-8">' .
            '<link href="shared/moodle.css" rel="stylesheet"></head><body>' .
            '<h1>Été &amp; cours</h1><a href="book/index.html">Livre</a>' .
            '<a href="page.html?view=1#part">Page</a><a href="#section">Section</a>' .
            '<a href="https://example.org/index.html">External</a>' .
            '<a href="files/original.html">Original</a>' .
            '<img src="files/image%20%C3%A9t%C3%A9.png" srcset="files/small.png 1x, files/large.png 2x">' .
            '<div style="background-image:url(files/small.png)">Image</div></body></html>';
        $documents = [
            'index.html' => $index,
            'page.html' => '<a href="index.html#section">Course</a><a href="book/index.html">Book</a>',
            'book/index.html' => '<a href="../index.html">Course</a><a href="chapter.html">Chapter</a>',
            'book/chapter.html' => '<a href="../index.html?x=1&amp;y=2#section">Course</a><a href="index.html">Book</a>',
            'shared/moodle.css' => 'body { color: black; }',
            'files/image été.png' => 'image',
            'files/small.png' => 'small',
            'files/large.png' => 'large',
            'files/original.html' => '<a href="index.html">Original bytes must stay identical</a>',
        ];
        $files = [];
        foreach ($documents as $name => $content) {
            $path = $directory . '/' . $name;
            check_dir_exists(dirname($path));
            file_put_contents($path, $content);
            $files[$name] = $path;
        }
        $result = layout::organise($files);
        $this->assertCount(count($documents), $result);
        foreach ($result as $name => $path) {
            $this->assertTrue($name === 'index.html' || str_starts_with($name, 'resources/'));
        }
        $html = file_get_contents($result['index.html']);
        $this->assertStringContainsString('href="resources/shared/moodle.css"', $html);
        $this->assertStringContainsString('href="resources/book/index.html"', $html);
        $this->assertStringContainsString('href="resources/page.html?view=1#part"', $html);
        $this->assertStringContainsString('href="#section"', $html);
        $this->assertStringContainsString('href="https://example.org/index.html"', $html);
        $this->assertStringContainsString('src="resources/files/image%20%C3%A9t%C3%A9.png"', $html);
        $this->assertStringContainsString('resources/files/small.png 1x, resources/files/large.png 2x', $html);
        $this->assertStringContainsString('url(resources/files/small.png)', $html);
        $this->assertStringContainsString('href="../index.html#section"', file_get_contents($result['resources/page.html']));
        $chapter = file_get_contents($result['resources/book/chapter.html']);
        $this->assertStringContainsString('href="../../index.html?x=1&amp;y=2#section"', $chapter);
        $this->assertStringContainsString('href="index.html"', $chapter);
        $zip = $directory . '/archive.zip';
        $packer = get_file_packer('application/zip');
        $this->assertTrue($packer->archive_to_pathname($result, $zip));
        $this->assertNotFalse($packer->extract_to_pathname($zip, $directory . '/unpacked'));
        $root = array_values(array_diff(scandir($directory . '/unpacked'), ['.', '..']));
        $this->assertSame(['index.html', 'resources'], $root);
        foreach ($documents as $name => $content) {
            if (str_starts_with($name, 'files/')) {
                $this->assertSame($content, file_get_contents($directory . '/unpacked/resources/' . $name));
            }
        }
    }
}
