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

namespace local_flatarchive\archive;

/**
 * Render delimited formulas in generated pages, without running Moodle filters.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class math_content {
    /**
     * Convert text nodes only; leave markup, attributes and literal examples alone.
     * @param \DOMDocument $dom Generated archive document
     */
    public static function render(\DOMDocument $dom): void {
        $xpath = new \DOMXPath($dom);
        $nodes = iterator_to_array($xpath->query('//body//text()'));
        $pattern = '~(?<!\\\\)(?:\\\\\((.*?)\\\\\)|\\\\\[(.*?)\\\\\]|\$\$(.*?)\$\$|\[tex\](.*?)\[/tex\])~s';
        foreach ($nodes as $node) {
            if (self::excluded($node) || !preg_match_all($pattern, $node->textContent, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $fragment = $dom->createDocumentFragment();
            $offset = 0;
            foreach ($matches[0] as $i => [$original, $start]) {
                $fragment->appendChild($dom->createTextNode(substr($node->textContent, $offset, $start - $offset)));
                $source = '';
                for ($group = 1; $group <= 4; $group++) {
                    if (($matches[$group][$i][1] ?? -1) >= 0) {
                        $source = $matches[$group][$i][0];
                        break;
                    }
                }
                $math = math_parser::convert($dom, $source, !str_starts_with($original, '\\('));
                if ($math === null) {
                    $math = $dom->createElement('span');
                    $math->setAttribute('class', 'flatarchive-math-unrendered');
                    $code = $dom->createElement('code');
                    $code->appendChild($dom->createTextNode($original));
                    $math->appendChild($code);
                    $notice = $dom->createElement('span');
                    $notice->setAttribute('class', 'flatarchive-math-notice');
                    $notice->appendChild($dom->createTextNode(' — ' . get_string('mathunrendered', 'local_flatarchive')));
                    $math->appendChild($notice);
                }
                $fragment->appendChild($math);
                $offset = $start + strlen($original);
            }
            $fragment->appendChild($dom->createTextNode(substr($node->textContent, $offset)));
            $node->parentNode->replaceChild($fragment, $node);
        }
    }

    /**
     * Do not reinterpret code, existing math, SVG, controls or opt-out content.
     * @param \DOMNode $node Text node
     * @return bool Whether rendering is inappropriate here
     */
    private static function excluded(\DOMNode $node): bool {
        for ($parent = $node->parentNode; $parent instanceof \DOMElement; $parent = $parent->parentNode) {
            $excludedtags = ['pre', 'code', 'kbd', 'samp', 'script', 'style', 'textarea',
                'select', 'option', 'math', 'svg'];
            if (in_array(strtolower($parent->tagName), $excludedtags, true)) {
                return true;
            }
            $ignored = '/(?:^|\s)(?:tex2jax_ignore|mathjax_ignore|flatarchive-math-unrendered)(?:\s|$)/';
            if (preg_match($ignored, $parent->getAttribute('class'))) {
                return true;
            }
        }
        return false;
    }
}
