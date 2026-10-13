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
 * Keep only the entry page at the root of a portable archive.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class layout {
    /**
     * Remap ZIP entries and links in generated documents without changing original attachments.
     *
     * @param array $files Archive paths mapped to workspace files
     * @return array Final archive paths mapped to workspace files
     */
    public static function organise(array $files): array {
        $destinations = [];
        foreach ($files as $name => $path) {
            $destinations[$name] = $name === 'index.html' ? $name : 'resources/' . $name;
        }
        $result = [];
        foreach ($files as $name => $path) {
            // HTML attachments are user files, not generated archive navigation.
            if (str_ends_with($name, '.html') && !str_starts_with($name, 'files/') && !str_starts_with($name, 'shared/')) {
                $html = self::rewrite(file_get_contents($path), $name, $destinations);
                \local_flatarchive_write_file($path, $html);
            }
            $result[$destinations[$name]] = $path;
        }
        return $result;
    }

    /**
     * Rewrite only URLs pointing to known archive entries.
     *
     * @param string $html Generated document
     * @param string $source Old document path
     * @param array $destinations Old to new archive paths
     * @return string Updated document
     */
    private static function rewrite(string $html, string $source, array $destinations): string {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET);
            foreach ((new \DOMXPath($dom))->query('//*') as $node) {
                foreach (['href', 'src', 'poster', 'data', 'background', 'xlink:href'] as $attribute) {
                    if ($node->hasAttribute($attribute)) {
                        $node->setAttribute($attribute, self::url($node->getAttribute($attribute), $source, $destinations));
                    }
                }
                if ($node->hasAttribute('srcset')) {
                    $srcset = preg_replace_callback(
                        '/(^|,\s*)([^\s,]+)(\s+[\d.]+[wx])?(?=\s*(?:,|$))/',
                        static function ($match) use ($source, $destinations) {
                            return $match[1] . self::url($match[2], $source, $destinations) . ($match[3] ?? '');
                        },
                        $node->getAttribute('srcset')
                    );
                    $node->setAttribute('srcset', $srcset);
                }
                if ($node->hasAttribute('style')) {
                    $node->setAttribute('style', self::css($node->getAttribute('style'), $source, $destinations));
                }
                if ($node->tagName === 'style') {
                    $node->textContent = self::css($node->textContent, $source, $destinations);
                }
            }
            // Remove the encoding hint used by libxml; preserve the document's real metadata.
            foreach (iterator_to_array($dom->childNodes) as $node) {
                if ($node->nodeType === XML_PI_NODE) {
                    $dom->removeChild($node);
                }
            }
            html_safety::harden($dom);
            return $dom->saveHTML();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Update local image references within inline CSS.
     * @param string $css Inline CSS
     * @param string $source Source document
     * @param array $destinations Path map
     * @return string Updated CSS
     */
    private static function css(string $css, string $source, array $destinations): string {
        return preg_replace_callback(
            '/url\(\s*([\'"]?)([^)\'"\s]+)\1\s*\)/i',
            static function ($match) use ($source, $destinations) {
                return 'url(' . $match[1] . self::url($match[2], $source, $destinations) . $match[1] . ')';
            },
            $css
        );
    }

    /**
     * Resolve a local URL and compute its new relative path, preserving query and fragment.
     * @param string $url Original URL
     * @param string $source Old source path
     * @param array $destinations Old to new archive paths
     * @return string URL relative to the new source path
     */
    private static function url(string $url, string $source, array $destinations): string {
        if (
            $url === '' || $url[0] === '#' || $url[0] === '?' || $url[0] === '/' ||
            preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)
        ) {
            return $url;
        }
        preg_match('/^([^?#]*)(.*)$/s', $url, $parts);
        $base = dirname($source) === '.' ? '' : dirname($source) . '/';
        $segments = [];
        foreach (explode('/', $base . rawurldecode($parts[1])) as $segment) {
            if ($segment === '..') {
                if (!$segments) {
                    return $url;
                }
                array_pop($segments);
            } else if ($segment !== '' && $segment !== '.') {
                $segments[] = $segment;
            }
        }
        $target = implode('/', $segments);
        if (!isset($destinations[$target])) {
            return $url;
        }
        $from = dirname($destinations[$source]);
        $from = $from === '.' ? [] : explode('/', $from);
        $to = explode('/', $destinations[$target]);
        while ($from && $to && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }
        return str_repeat('../', count($from)) . implode('/', array_map('rawurlencode', $to)) . $parts[2];
    }
}
