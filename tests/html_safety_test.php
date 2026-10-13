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

use local_flatarchive\archive\html_safety;
use local_flatarchive\archive\layout;

/**
 * Generated HTML is passive, while original attachments retain their bytes.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\html_safety
 * @covers \local_flatarchive\archive\layout
 */
final class html_safety_test extends \advanced_testcase {
    /**
     * Exercise the actual archive finalisation, including malicious metadata and handlers.
     */
    public function test_active_html_is_disabled_and_attachments_unchanged(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        $this->resetAfterTest();
        $directory = make_temp_directory('flatarchive-safety');
        $source = '<!doctype html><html><head><base href="https://attacker.invalid/">'
            . '<meta http-equiv="refresh" content="0;url=https://attacker.invalid/"></head><body>'
            . '<script>alert(1)</script><img src="safe.png" onerror="alert(1)">'
            . '<a id="unsafe" href="java&#x09;script:alert(1)">Unsafe</a>'
            . '<a id="safe" href="page.html#section">Page</a>'
            . '<form action="https://attacker.invalid/"><input value="preserved"><button>Send</button></form>'
            . '<iframe src="https://example.org/video" title="Video"></iframe>'
            . '<iframe srcdoc="&lt;script&gt;alert(1)&lt;/script&gt;"></iframe>'
            . '<object data="payload.html"></object></body></html>';
        file_put_contents($directory . '/index.html', $source);
        file_put_contents($directory . '/attachment.html', $source);
        layout::organise([
            'index.html' => $directory . '/index.html',
            'files/attachment.html' => $directory . '/attachment.html',
        ]);
        $this->assertSame($source, file_get_contents($directory . '/attachment.html'));
        $dom = new \DOMDocument();
        $dom->loadHTML(file_get_contents($directory . '/index.html'), LIBXML_NONET);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//script|//base|//iframe|//object|//form|//*[@onerror]')->length);
        $this->assertSame(0, $xpath->query('//a[@id="unsafe"]/@href')->length);
        $this->assertSame(0, $xpath->query('//meta[@http-equiv="refresh"]')->length);
        $this->assertSame(1, $xpath->query('//input[@disabled][@value="preserved"]')->length);
        $this->assertSame(1, $xpath->query('//a[@href="https://example.org/video"]')->length);
        $this->assertSame(1, $xpath->query('//a[@id="safe"][@href="page.html#section"]')->length);
        $policy = $xpath->query('//head/meta[@http-equiv="Content-Security-Policy"]')->item(0);
        $this->assertSame(html_safety::POLICY, $policy->getAttribute('content'));
        $this->assertSame($policy, $dom->getElementsByTagName('head')->item(0)->firstChild);
    }
}
