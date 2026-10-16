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
 * Shared selection policy for the interface and the export endpoint.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class selection {
    /** @var array Required module capabilities, in addition to course archive permission. */
    private const CAPABILITIES = [
        'page' => ['mod/page:view'],
        'book' => ['mod/book:read'],
        'label' => [],
        'resource' => ['mod/resource:view'],
        'folder' => ['mod/folder:view'],
        'glossary' => ['mod/glossary:view'],
        'wiki' => ['mod/wiki:viewpage'],
        'feedback' => ['mod/feedback:view'],
        'choice' => ['mod/choice:view'],
        'forum' => ['mod/forum:viewdiscussion'],
        'lesson' => ['mod/lesson:view', 'mod/lesson:edit'],
        'quiz' => ['mod/quiz:view'],
        'data' => ['mod/data:viewentry', 'mod/data:manageentries'],
        'url' => ['mod/url:view'],
        'assign' => ['mod/assign:view'],
        'h5pactivity' => ['mod/h5pactivity:view', 'moodle/course:manageactivities'],
        'workshop' => ['mod/workshop:view', 'moodle/course:manageactivities'],
    ];

    /**
     * Whether an exporter exists for a module type.
     * @param string $type Module type
     * @return bool
     */
    public static function supported(string $type): bool {
        return self::native($type) || \local_flatarchive\extension\registry::installed()->available($type);
    }

    /**
     * Whether the free core owns this activity type.
     * @param string $type Module type
     * @return bool
     */
    public static function native(string $type): bool {
        return array_key_exists($type, self::CAPABILITIES);
    }

    /**
     * Check visibility and module capabilities for the current user.
     * @param \cm_info $cm Course module
     * @return bool
     */
    public static function permitted(\cm_info $cm): bool {
        if (!self::native($cm->modname)) {
            return \local_flatarchive\extension\registry::installed()->permitted($cm);
        }
        if (!$cm->uservisible) {
            return false;
        }
        foreach (self::CAPABILITIES[$cm->modname] as $capability) {
            if (!has_capability($capability, $cm->context)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate every requested ID before creating an archive workspace.
     * @param \course_modinfo $modinfo Authorised course module information
     * @param array $ids Requested course module IDs
     */
    public static function validate(\course_modinfo $modinfo, array $ids): void {
        $cms = $modinfo->get_cms();
        foreach ($ids as $id) {
            if (!isset($cms[$id]) || !self::permitted($cms[$id])) {
                throw new \moodle_exception('invalidselection', 'local_flatarchive');
            }
        }
    }
}
