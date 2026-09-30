<?php
namespace local_flatarchive\archive;

defined('MOODLE_INTERNAL') || die();

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
}
