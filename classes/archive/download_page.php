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
 * Read-only download confirmation independent of the archive generation POST.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class download_page {
    /**
     * Canonical URL retained by Moodle's language menu.
     * @param string $token Session-owned archive token
     * @return \moodle_url Confirmation URL
     */
    public static function url(string $token): \moodle_url {
        return new \moodle_url('/local/flatarchive/download.php', ['token' => $token]);
    }

    /**
     * Resolve and describe an existing archive without changing its contents or expiry.
     * @param string $token Session-owned archive token
     * @return array Course, archive and translated display information
     */
    public static function get(string $token): array {
        $archive = temporary_archive::resolve($token);
        $course = get_course($archive['courseid']);
        require_capability('local/flatarchive:archive', \context_course::instance($course->id));
        clearstatcache(true, $archive['path']);
        $size = filesize($archive['path']);
        return [
            'course' => $course,
            'archive' => $archive,
            'message' => $size === false ? get_string('archive_success', 'local_flatarchive') :
                get_string('archive_success_size', 'local_flatarchive', display_size($size)),
            'downloadurl' => new \moodle_url('/local/flatarchive/serve_temp.php', ['token' => $token, 'sesskey' => sesskey()]),
        ];
    }
}
