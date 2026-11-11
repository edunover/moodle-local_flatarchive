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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

namespace local_flatarchive;

use local_flatarchive\archive\label_exporter;
use local_flatarchive\archive\layout;
use local_flatarchive\archive\lesson_exporter;
use local_flatarchive\archive\math_content;
use local_flatarchive\archive\math_parser;

/**
 * Passive mathematics, conservative fallback and export-wide integration.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\math_parser
 * @covers \local_flatarchive\archive\math_content
 * @covers \local_flatarchive\archive\layout
 */
final class math_content_test extends \advanced_testcase {
    /**
     * Mathematical structure and source must both survive conversion.
     * @dataProvider formula_provider
     * @param string $source Formula
     * @param string $query Structural XPath assertion
     */
    public function test_formula_structure(string $source, string $query): void {
        $dom = new \DOMDocument();
        $math = math_parser::convert($dom, $source, true);
        $this->assertNotNull($math);
        $dom->appendChild($math);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('m', 'http://www.w3.org/1998/Math/MathML');
        $this->assertSame(1, $xpath->query($query)->length, $dom->saveXML());
        $this->assertSame($source, $xpath->query('//m:annotation')->item(0)->textContent);
        $this->assertSame('application/x-tex', $xpath->query('//m:annotation')->item(0)->getAttribute('encoding'));
        $this->assertSame('block', $math->getAttribute('display'));
    }

    /**
     * Known mathematical layouts, including TeX's single-token arguments.
     * @return array Cases
     */
    public static function formula_provider(): array {
        return [
            'Pythagoras' => ['a^2+b^2=c^2', '//m:msup[m:mi="c" and m:mn="2"]'],
            'fraction' => ['\\frac{1}{2}', '//m:mfrac[m:mrow[1]="1" and m:mrow[2]="2"]'],
            'single token fraction' => ['\\frac12', '//m:mfrac[m:mn[1]="1" and m:mn[2]="2"]'],
            'single token exponent' => ['x^12', '//m:msup[m:mi="x" and m:mn="1"]/following-sibling::m:mn[.="2"]'],
            'quadratic' => ['\\frac{-b\\pm\\sqrt{b^2-4ac}}{2a}', '//m:mfrac/m:mrow/m:msqrt'],
            'cube root' => ['\\sqrt[3]{x}', '//m:mroot[m:mrow[1]="x" and m:mrow[2]="3"]'],
            'both scripts' => ['x_1^2', '//m:msubsup[m:mi="x" and m:mn[1]="1" and m:mn[2]="2"]'],
            'sum limits' => ['\\sum_{i=1}^{n} i', '//m:munderover[m:mo="∑"]'],
            'integral' => ['\\int_0^1 x\\,\\mathrm{d}x', '//m:msubsup[m:mo="∫"]'],
            'Greek and function' => ['\\sin(\\alpha)+\\pi', '//m:mi[@mathvariant="normal" and .="sin"]'],
            'scalable brackets' => ['\\left(\\frac{a}{b}\\right)', '//m:mo[@stretchy="true" and .=")"]'],
            'invisible bracket' => ['\\left. x \\right|_0^1', '//m:mo[@stretchy="true" and .=""]'],
            'text spaces' => ['x+\\text{longueur totale}', '//m:mtext[.="longueur totale"]'],
            'escaped characters' => ['50\\%', '//m:mo[.="%"]'],
            'vector accent' => ['\\vec{x}', '//m:mover[@accent="true"]'],
            'source cannot inject HTML' => ['\\text{<img src=x onerror=alert(1)>}', '//m:mtext'],
        ];
    }

    /**
     * Invalid/unsupported input fails as a whole instead of lying about the formula.
     * @dataProvider rejected_provider
     * @param string $source Formula
     */
    public function test_rejected_formula(string $source): void {
        $dom = new \DOMDocument();
        $this->assertNull(math_parser::convert($dom, $source, false));
        $this->assertFalse($dom->hasChildNodes());
    }

    /**
     * No macro expansion, arbitrary markup, I/O or unbounded recursion.
     * @return array Cases
     */
    public static function rejected_provider(): array {
        return [
            'empty' => [' '], 'unknown' => ['x+\\unknown{y}'], 'unfinished fraction' => ['\\frac{1}'],
            'unclosed' => ['{x+1'], 'extra brace' => ['x}'], 'missing base' => ['^2'],
            'repeated script' => ['x^2^3'], 'missing exponent' => ['x^'],
            'missing right' => ['\\left(x'], 'stray right' => ['x\\right)'],
            'macros' => ['\\newcommand{\\foo}{x}\\foo'], 'file read' => ['\\input{/etc/passwd}'],
            'shell' => ['\\write18{touch /tmp/unsafe}'], 'link' => ['\\href{javascript:alert(1)}{x}'],
            'HTML' => ['\\htmlClass{foo}{x}'], 'environment' => ['\\begin{matrix}a&b\\end{matrix}'],
            'nested text command' => ['\\text{\\input{secret}}'], 'unfinished text' => ['\\text{x'],
            'oversize' => [str_repeat('a', 4097)], 'too many tokens' => [str_repeat('x+', 513)],
            'deep groups' => [str_repeat('{', 40) . 'x' . str_repeat('}', 40)],
            'deep commands' => [str_repeat('\\sqrt', 40) . 'x'], 'control' => ["x\x00y"],
        ];
    }

    /**
     * Mixed text, escaped delimiters, source examples and opt-outs remain distinguishable.
     */
    public function test_content_boundaries_and_fallback(): void {
        $this->resetAfterTest();
        $html = '<html><head><title>\\(title\\)</title></head><body>' .
            '<p title="\\(attribute\\)">{mlang fr}Prix 5 $ et 10 $. ' .
            '\\(x^2\\) puis \\[\\frac{1}{2}\\] et $$\\alpha$$ [tex]a_1[/tex]{mlang}</p>' .
            '<p>\\(x+\\unknown{y}\\)</p>' .
            '<pre>$$example$$</pre><code>\\(code\\)</code><textarea>$$input$$</textarea>' .
            '<span class="tex2jax_ignore">$$ignored$$</span><span class="mathjax_ignore">$$ignored$$</span>' .
            '<p>\\\\(escaped\\)</p><p>\\(unfinished</p><p>\\(<b>split</b>\\)</p>' .
            '<math><mi>x</mi></math></body></html>';
        $dom = $this->document($html);
        math_content::render($dom);
        $xpath = new \DOMXPath($dom);
        $query = '//*[local-name()="math" and @class="flatarchive-math"]';
        $this->assertSame(4, $xpath->query($query)->length);
        $this->assertSame('inline', $xpath->query($query)->item(0)->getAttribute('display'));
        $this->assertSame('block', $xpath->query($query)->item(1)->getAttribute('display'));
        $this->assertSame(1, $xpath->query('//span[@class="flatarchive-math-unrendered"]')->length);
        $this->assertSame('\\(x+\\unknown{y}\\)', $xpath->query('//span/code')->item(0)->textContent);
        $this->assertStringContainsString(get_string('mathunrendered', 'local_flatarchive'), $dom->textContent);
        $this->assertSame('$$example$$', $xpath->query('//pre')->item(0)->textContent);
        $this->assertSame('\\(title\\)', $xpath->query('//title')->item(0)->textContent);
        $this->assertSame('\\(attribute\\)', $xpath->query('//p')->item(0)->getAttribute('title'));
        $this->assertStringContainsString('{mlang fr}Prix 5 $ et 10 $.', $dom->textContent);
        $this->assertStringContainsString('\\\\(escaped\\)', $dom->textContent);
        $before = $dom->saveHTML();
        math_content::render($dom);
        $this->assertSame($before, $dom->saveHTML(), 'Rendering is idempotent, including failed formulas.');
    }

    /**
     * Real label/lesson content and a formatted Page become portable passive HTML.
     */
    public function test_exported_content_and_zip(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        require_once($CFG->libdir . '/filelib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $source = '<p>{mlang fr}Formule{mlang}{mlang en}Formula{mlang}: ' .
            '\\(a^2+b^2=c^2\\)</p><p>$$\\frac{1}{2}$$</p>';
        $label = $generator->create_module('label', [
            'course' => $course->id, 'intro' => $source, 'introformat' => FORMAT_HTML,
        ]);
        $lesson = $generator->create_module('lesson', [
            'course' => $course->id, 'intro' => $source, 'introformat' => FORMAT_HTML,
        ]);
        $page = $generator->create_module('page', [
            'course' => $course->id, 'content' => $source, 'contentformat' => FORMAT_HTML,
        ]);
        $directory = make_request_directory();
        $info = get_fast_modinfo($course);
        $export = lesson_exporter::render($info->get_cm($lesson->cmid), $info, $directory);
        $files = [$export['filename'] => $directory . '/' . $export['filename']];
        $labelhtml = label_exporter::render($info->get_cm($label->cmid), $info, $directory);
        // Match the Page controller's native formatting, with all content filters off.
        $pagehtml = format_text($page->content, $page->contentformat, [
            'context' => \context_module::instance($page->cmid), 'filter' => false,
        ]);
        foreach (['index.html' => $labelhtml, 'page.html' => $pagehtml] as $name => $body) {
            $files[$name] = $directory . '/' . $name;
            local_flatarchive_write_file($files[$name], '<html><body>' . $body . '</body></html>');
        }
        mkdir($directory . '/files');
        $files['files/original.html'] = $directory . '/files/original.html';
        local_flatarchive_write_file($files['files/original.html'], $source);
        $files = layout::organise($files);
        $packer = get_file_packer('application/zip');
        $this->assertTrue($packer->archive_to_pathname($files, $directory . '/test.zip'));
        $this->assertNotFalse($packer->extract_to_pathname($directory . '/test.zip', $directory . '/out'));
        foreach (['index.html', 'resources/page.html', 'resources/' . $export['filename']] as $name) {
            $html = file_get_contents($directory . '/out/' . $name);
            $dom = $this->document($html);
            $xpath = new \DOMXPath($dom);
            $this->assertSame(2, $xpath->query('//*[local-name()="math"]')->length);
            $this->assertSame(1, $xpath->query('//*[local-name()="mfrac"]')->length);
            $this->assertStringContainsString("script-src 'none'", $html);
            $this->assertSame(0, $xpath->query('//script | //iframe | //object')->length);
            $this->assertStringContainsString('{mlang fr}Formule{mlang}', $html);
            $this->assertStringNotContainsString('cdn.', $html);
        }
        $this->assertSame($source, file_get_contents($directory . '/out/resources/files/original.html'));
        foreach (['label' => $label, 'lesson' => $lesson, 'page' => $page] as $table => $record) {
            $this->assertSame($source, $DB->get_field($table, $table === 'page' ? 'content' : 'intro', ['id' => $record->id]));
        }
    }

    /**
     * Parse HTML including modern elements unknown to libxml's HTML4 grammar.
     * @param string $html Document
     * @return \DOMDocument Parsed document
     */
    private function document(string $html): \DOMDocument {
        $previous = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument();
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET);
            return $dom;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
