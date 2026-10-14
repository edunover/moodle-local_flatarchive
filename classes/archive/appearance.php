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
 * Institution CSS and stable styling hooks for generated documents only.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class appearance {
    /**
     * Editable starter styles; loading settings never replaces saved custom CSS.
     * @return array Preset keys and stylesheet source
     */
    public static function presets(): array {
        global $CFG;
        $styles = ['none' => ''];
        foreach (['institutional', 'modern', 'classic'] as $name) {
            $styles[$name] = file_get_contents($CFG->dirroot . '/local/flatarchive/style/presets/' . $name . '.css');
        }
        return $styles;
    }

    /**
     * Write the saved CSS as a separate passive asset, after the baseline stylesheet.
     * @param string $directory Export workspace
     * @return array Archive files
     */
    public static function write(string $directory): array {
        $path = $directory . '/shared/institution.css';
        \local_flatarchive_write_file($path, (string)get_config('local_flatarchive', 'customcss'));
        return ['shared/institution.css' => $path];
    }

    /**
     * Add hooks to every body element and load the optional institution stylesheet last.
     * @param \DOMDocument $dom Generated document after layout rewriting and sanitisation
     * @param bool $stylesheet Whether the custom asset is registered
     */
    public static function decorate(\DOMDocument $dom, bool $stylesheet): void {
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//body | //body//*') as $node) {
            $tag = strtolower($node->nodeName);
            $classes = preg_split('/\s+/', trim($node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
            $classes[] = 'flatarchive-' . $tag;
            foreach ($classes as $class) {
                if (str_starts_with($class, 'fa-')) {
                    $classes[] = 'flatarchive-' . substr($class, strlen('fa-'));
                }
            }
            foreach (['card', 'card-body', 'navbar', 'navbar-brand'] as $role) {
                if (in_array($role, $classes, true)) {
                    $classes[] = 'flatarchive-' . $role;
                }
            }
            $semantic = ['body' => 'flatarchive-archive', 'main' => 'flatarchive-main',
                'h1' => 'flatarchive-title', 'footer' => 'flatarchive-footer'];
            if (isset($semantic[$tag])) {
                $classes[] = $semantic[$tag];
            }
            $node->setAttribute('class', implode(' ', array_unique($classes)));
        }
        if (!$stylesheet) {
            return;
        }
        foreach ($xpath->query('//link[@rel="stylesheet"]') as $base) {
            $href = $base->getAttribute('href');
            if (str_ends_with($href, 'shared/moodle.css')) {
                $link = $dom->createElement('link');
                $link->setAttribute('rel', 'stylesheet');
                $link->setAttribute('href', substr($href, 0, -strlen('moodle.css')) . 'institution.css');
                $dom->getElementsByTagName('head')->item(0)->appendChild($link);
                break;
            }
        }
    }
}
