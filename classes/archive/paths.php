<?php
// This file is part of Moodle - https://moodle.org/
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
 * Collision-free paths within an offline archive.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class paths {
    /**
     * Keep a readable title while reserving an unambiguous identity suffix.
     *
     * @param string $title Resource title.
     * @param string $kind page, book or chapter.
     * @param int $id Unique resource identifier.
     * @return string Safe filename stem.
     */
    public static function stem(string $title, string $kind, int $id): string {
        if (!in_array($kind, ['page', 'book', 'chapter'], true) || $id <= 0) {
            throw new \coding_exception('Invalid archive resource identity');
        }
        $name = clean_filename($title);
        $name = preg_replace('/[<>:"\/\\\\|?*\x00-\x1f]/u', '_', $name);
        $name = trim(\core_text::substr($name, 0, 40), " .\t\n\r\0\x0B");
        return ($name !== '' ? $name : $kind) . '--' . $kind . '-' . $id;
    }

    /**
     * Isolate different file areas and chapter IDs, even with identical filenames.
     *
     * @param int $cmid Module ID.
     * @param string $filearea File area.
     * @param int $itemid Item ID.
     * @return string Relative directory, without a trailing slash.
     */
    public static function assets(int $cmid, string $filearea, int $itemid): string {
        if ($cmid <= 0 || $itemid < 0 || !preg_match('/\A[a-z][a-z0-9_]*\z/', $filearea)) {
            throw new \coding_exception('Invalid archive file area');
        }
        return 'files/' . $cmid . '/' . $filearea . '/' . $itemid;
    }

    /**
     * Reject paths which could escape a file area or package directory.
     * @param string $path Candidate path
     * @return bool Whether the path is a plain relative path
     */
    public static function relative(string $path): bool {
        return $path !== '' && !preg_match('~[\\\\:\x00-\x1f]|^/|(?:^|/)\.{1,2}(?:/|$)|//~', $path);
    }
}
