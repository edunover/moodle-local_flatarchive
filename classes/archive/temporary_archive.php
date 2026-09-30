<?php
namespace local_flatarchive\archive;

defined('MOODLE_INTERNAL') || die();

/**
 * Session-owned temporary archives. No client-supplied filesystem paths.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class temporary_archive {
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
        $SESSION->local_flatarchive_archives[$token] = [
            'userid' => (int)$USER->id,
            'courseid' => $courseid,
            'filename' => clean_filename($filename),
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
        if (!$entry || (int)$entry['userid'] !== (int)$USER->id) {
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
     * Forget a successfully downloaded archive and remove just its workspace.
     * @param string $token Workspace token.
     */
    public static function delete(string $token): void {
        global $CFG, $SESSION;
        require_once($CFG->libdir . '/filelib.php');
        $entry = self::resolve($token);
        fulldelete($entry['directory']);
        unset($SESSION->local_flatarchive_archives[$token]);
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
