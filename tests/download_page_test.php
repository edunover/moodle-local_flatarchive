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

namespace local_flatarchive;

use local_flatarchive\archive\download_page;
use local_flatarchive\archive\temporary_archive;

/**
 * Reloadable, language-independent archive confirmation.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\download_page
 */
final class download_page_test extends \advanced_testcase {
    /**
     * Language navigation needs only the token and never mutates the generated archive.
     */
    public function test_language_changes_reuse_archive(): void {
        global $SESSION;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $workspace = temporary_archive::create();
        file_put_contents($workspace['directory'] . '/archive.zip', 'Original export language');
        temporary_archive::register($workspace['token'], $course->id, 'Course.zip');
        $original = temporary_archive::resolve($workspace['token']);
        $previous = current_language();
        try {
            foreach (['en', 'fr', 'nl', 'en'] as $language) {
                force_current_language($language);
                $url = new \moodle_url(download_page::url($workspace['token']), ['lang' => $language]);
                $data = download_page::get($url->get_param('token'));
                $this->assertSame($original, $data['archive']);
                $this->assertSame('Original export language', file_get_contents($data['archive']['path']));
                $this->assertSame($workspace['token'], $data['downloadurl']->get_param('token'));
                $this->assertSame(sesskey(), $data['downloadurl']->get_param('sesskey'));
                $this->assertStringContainsString('/local/flatarchive/download.php?', $url->out(false));
                $this->assertStringNotContainsString('sesskey', $url->out(false));
                $this->assertNotEmpty($data['message']);
            }
        } finally {
            force_current_language($previous);
        }
        $this->assertCount(1, $SESSION->local_flatarchive_archives);
        $SESSION->local_flatarchive_archives[$workspace['token']]['expiresat'] = time() - 1;
        $this->expectException(\moodle_exception::class);
        download_page::get($workspace['token']);
    }

    /**
     * A previously generated ZIP does not bypass revoked course archive capability.
     */
    public function test_revoked_capability_is_checked_again(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);
        $workspace = temporary_archive::create();
        file_put_contents($workspace['directory'] . '/archive.zip', 'Archive');
        temporary_archive::register($workspace['token'], $course->id, 'Course.zip');
        $this->assertSame($course->id, download_page::get($workspace['token'])['course']->id);
        $role = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        assign_capability('local/flatarchive:archive', CAP_PROHIBIT, $role, \context_course::instance($course->id)->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->expectException(\required_capability_exception::class);
        download_page::get($workspace['token']);
    }
}
