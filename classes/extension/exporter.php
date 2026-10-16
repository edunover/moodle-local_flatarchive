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

namespace local_flatarchive\extension;

/**
 * Contract for an optional, trusted local-plugin exporter (API version 1).
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface exporter {
    /**
     * Target activity plugin name, without mod_.
     * @return string
     */
    public function module(): string;

    /**
     * Explicit Moodle branches, e.g. [405, 500]. Empty means unavailable.
     * @return int[]
     */
    public function branches(): array;

    /**
     * Additional access policy, including module capabilities and groups.
     * @param \cm_info $cm Activity
     * @return bool
     */
    public function permitted(\cm_info $cm): bool;

    /**
     * Generate static documents in the assigned activity directory.
     *
     * Honour settings/reports options and native permissions for every exported record.
     * Store original attachments below files/ to preserve their contents.
     * Return a relative main HTML filename and all relative generated filenames.
     * Use relative links inside this directory and ../../shared/ for shared assets.
     * Back-to-course links must point to ../../index.html before layout relocation.
     *
     * @param \cm_info $cm Activity
     * @param \course_modinfo $modinfo Course
     * @param string $directory Assigned directory, not the archive root
     * @param array $options Selected course options
     * @return array Keys filename (string), filenames (string[])
     */
    public function render(\cm_info $cm, \course_modinfo $modinfo, string $directory, array $options): array;
}
