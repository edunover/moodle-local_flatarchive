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
 * Session-owned temporary archives. No client-supplied filesystem paths.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class temporary_archive {
    /** @var int Download availability in seconds after generation. */
    public const LIFETIME = HOURSECS;

    /**
     * Create a distinct workspace for every export.
     * @return array Token and directory.
     */
    public static function create(): array {
        $token = bin2hex(random_bytes(24));
        return ['token' => $token, 'directory' => make_temp_directory('flatarchive/' . $token)];
    }

    /**
     * Register a successfully generated ZIP in the current user's session.
     * @param string $token Workspace token.
     * @param int $courseid Course ID.
     * @param string $filename Download filename.
     */
    public static function register(string $token, int $courseid, string $filename): void {
        global $SESSION, $USER;
        $directory = self::directory($token);
        if (!is_file($directory . '/archive.zip') || is_link($directory . '/archive.zip')) {
            throw new \moodle_exception('archivefailed', 'local_flatarchive');
        }
        foreach ($SESSION->local_flatarchive_archives ?? [] as $oldtoken => $entry) {
            if (($entry['expiresat'] ?? 0) <= time()) {
                unset($SESSION->local_flatarchive_archives[$oldtoken]);
            }
        }
        $SESSION->local_flatarchive_archives[$token] = [
            'userid' => (int)$USER->id,
            'courseid' => $courseid,
            'filename' => clean_filename($filename),
            'expiresat' => time() + self::LIFETIME,
        ];
    }

    /**
     * Resolve only a ZIP belonging to this authenticated user and session.
     * @param string $token Workspace token.
     * @return array Validated download information.
     */
    public static function resolve(string $token): array {
        global $SESSION, $USER;
        $entry = $SESSION->local_flatarchive_archives[$token] ?? null;
        if (!$entry || (int)$entry['userid'] !== (int)$USER->id || ($entry['expiresat'] ?? 0) <= time()) {
            throw new \moodle_exception('filenotfound', 'error');
        }
        $directory = self::directory($token);
        $file = $directory . '/archive.zip';
        if (!is_file($file) || !is_readable($file) || is_link($file)) {
            throw new \moodle_exception('filenotfound', 'error');
        }
        return $entry + ['directory' => $directory, 'path' => $file];
    }

    /**
     * Reject invalid tokens, symlinked folders and paths outside the export root.
     * @param string $token Workspace token.
     * @return string Canonical directory.
     */
    private static function directory(string $token): string {
        global $CFG;
        if (!preg_match('/\A[a-f0-9]{48}\z/', $token)) {
            throw new \moodle_exception('filenotfound', 'error');
        }
        $root = realpath($CFG->tempdir . '/flatarchive');
        $directory = $root === false ? false : realpath($root . '/' . $token);
        if ($directory === false || $directory !== $root . '/' . $token || !is_dir($directory)) {
            throw new \moodle_exception('filenotfound', 'error');
        }
        return $directory;
    }
}
