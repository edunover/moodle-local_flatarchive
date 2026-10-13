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
 * Disable active behaviour in generated documents; original attachments are not transformed.
 *
 * Moodle's content cleaning remains the primary HTML sanitiser. This final pass adds
 * a restrictive browser policy and removes active navigation and form elements.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class html_safety {
    /** @var string Policy for generated static pages, including pages opened from disk. */
    public const POLICY = "default-src 'none'; script-src 'none'; object-src 'none'; frame-src 'none'; "
        . "base-uri 'none'; form-action 'none'; connect-src 'none'; "
        . "img-src 'self' file: data: https: http:; media-src 'self' file: https: http:; "
        . "style-src 'self' file: 'unsafe-inline'; font-src 'self' file: data:";

    /**
     * Harden a generated document before serialising it into the ZIP.
     * @param \DOMDocument $dom Generated document
     */
    public static function harden(\DOMDocument $dom): void {
        $xpath = new \DOMXPath($dom);
        foreach (iterator_to_array($xpath->query('//script|//base|//object|//embed|//meta[@http-equiv]')) as $node) {
            $node->parentNode->removeChild($node);
        }
        // Preserve access to external media without running a remote embedded application.
        foreach (iterator_to_array($xpath->query('//iframe')) as $node) {
            $url = $node->getAttribute('src');
            if (preg_match('~^https?://~i', $url)) {
                $link = $dom->createElement('a');
                $link->setAttribute('href', $url);
                $link->appendChild($dom->createTextNode($node->getAttribute('title') ?: $url));
                $node->parentNode->replaceChild($link, $node);
            } else {
                $node->parentNode->removeChild($node);
            }
        }
        foreach (iterator_to_array($xpath->query('//form')) as $node) {
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
        }
        foreach ($xpath->query('//*') as $node) {
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if (str_starts_with($name, 'on') || in_array($name, ['srcdoc', 'formaction', 'autofocus'], true)) {
                    $node->removeAttributeNode($attribute);
                } else if (in_array($name, ['href', 'src', 'xlink:href', 'action', 'poster'], true)) {
                    $value = preg_replace('/[\x00-\x20\x7f]+/', '', $attribute->value);
                    if (
                        preg_match('/^(?:javascript|vbscript):/i', $value) ||
                            ($name === 'href' && preg_match('/^data:/i', $value))
                    ) {
                        $node->removeAttributeNode($attribute);
                    }
                }
            }
            if (in_array(strtolower($node->nodeName), ['input', 'button', 'select', 'textarea'], true)) {
                $node->setAttribute('disabled', 'disabled');
            }
            if (strtolower($node->nodeName) === 'a') {
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }
        $head = $dom->getElementsByTagName('head')->item(0);
        if (!$head) {
            $head = $dom->createElement('head');
            $dom->documentElement->insertBefore($head, $dom->documentElement->firstChild);
        }
        $policy = $dom->createElement('meta');
        $policy->setAttribute('http-equiv', 'Content-Security-Policy');
        $policy->setAttribute('content', self::POLICY);
        $head->insertBefore($policy, $head->firstChild);
        $referrer = $dom->createElement('meta');
        $referrer->setAttribute('name', 'referrer');
        $referrer->setAttribute('content', 'no-referrer');
        $head->appendChild($referrer);
    }
}
