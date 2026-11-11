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

/**
 * Test-only plagiarism integration: reproduce a plugin loading an in-memory usage ID.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_plugin_flatarchivefixture {
    /** @var array Calls made by the native plagiarism dispatcher. */
    public static array $calls = [];

    /**
     * Simulate a plugin that needs a persisted question usage, without external services.
     *
     * @param array $data Moodle's plagiarism request
     * @return string Test report marker
     */
    public function get_links($data): string {
        self::$calls[] = $data;
        \question_engine::load_questions_usage_by_activity($data['area']);
        return 'DO-NOT-EXPORT-PLAGIARISM-REPORT';
    }
}
