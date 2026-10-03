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

use local_flatarchive\archive\temporary_archive;

/**
 * Temporary archive access and cleanup regression tests.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\temporary_archive
 */
final class temporary_archive_test extends \advanced_testcase {
    /**
     * Isolate session and filesystem changes.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Create an available archive.
     *
     * @return array Registered fixture archive.
     */
    private function archive(): array {
        $workspace = temporary_archive::create();
        file_put_contents($workspace['directory'] . '/archive.zip', 'test archive');
        temporary_archive::register($workspace['token'], 42, 'Course.zip');
        return $workspace;
    }

    /**
     * Simultaneous exports remain distinct and cleanup leaves neighbours intact.
     */
    public function test_unique_workspaces_and_scoped_cleanup(): void {
        global $SESSION;
        $first = $this->archive();
        $second = $this->archive();
        $this->assertNotSame($first['token'], $second['token']);
        $resolved = temporary_archive::resolve($first['token']);
        $this->assertSame(42, $resolved['courseid']);
        $this->assertSame('Course.zip', $resolved['filename']);
        $this->assertSame('test archive', file_get_contents($resolved['path']));
        temporary_archive::delete($first['token']);
        $this->assertDirectoryDoesNotExist($first['directory']);
        $this->assertArrayNotHasKey($first['token'], $SESSION->local_flatarchive_archives);
        $this->assertFileExists(temporary_archive::resolve($second['token'])['path']);
    }

    /**
     * Reading the complete ZIP does not consume its token or change its expiry.
     */
    public function test_repeated_download_reads_keep_archive_available(): void {
        $workspace = $this->archive();
        $first = temporary_archive::resolve($workspace['token']);
        $this->assertSame('test archive', file_get_contents($first['path']));
        $second = temporary_archive::resolve($workspace['token']);
        $this->assertSame($first, $second);
        $this->assertSame('test archive', file_get_contents($second['path']));
        $this->assertFileExists($first['path']);
    }

    /**
     * Expired tokens cannot extend the download window.
     */
    public function test_expired_archive_is_rejected(): void {
        global $SESSION;
        $workspace = $this->archive();
        $SESSION->local_flatarchive_archives[$workspace['token']]['expiresat'] = time() - 1;
        $this->expectException(\moodle_exception::class);
        temporary_archive::resolve($workspace['token']);
    }

    /**
     * Cleaned-up files return the same unavailable result as expired archives.
     */
    public function test_cleaned_archive_is_rejected(): void {
        $workspace = $this->archive();
        unlink($workspace['directory'] . '/archive.zip');
        $this->expectException(\moodle_exception::class);
        temporary_archive::resolve($workspace['token']);
    }

    /**
     * A different user is rejected even if session metadata is present.
     */
    public function test_other_user_cannot_resolve_archive(): void {
        $workspace = $this->archive();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\moodle_exception::class);
        temporary_archive::resolve($workspace['token']);
    }

    /**
     * A known token alone is insufficient outside the owner's session.
     */
    public function test_unknown_session_cannot_resolve_archive(): void {
        global $SESSION;
        $workspace = $this->archive();
        unset($SESSION->local_flatarchive_archives);
        $this->expectException(\moodle_exception::class);
        temporary_archive::resolve($workspace['token']);
    }

    /**
     * Missing ZIPs are never registered as successful exports.
     */
    public function test_missing_zip_cannot_be_registered(): void {
        $workspace = temporary_archive::create();
        $this->expectException(\moodle_exception::class);
        temporary_archive::register($workspace['token'], 42, 'Course.zip');
    }

    /**
     * Raw paths cannot be used even with an injected session entry.
     */
    public function test_path_traversal_is_rejected(): void {
        global $SESSION, $USER;
        $token = '../outside';
        $SESSION->local_flatarchive_archives[$token] = [
            'userid' => (int)$USER->id,
            'expiresat' => time() + temporary_archive::LIFETIME,
        ];
        $this->expectException(\moodle_exception::class);
        temporary_archive::resolve($token);
    }

    /**
     * A ZIP replaced with a symlink must not expose another temporary file.
     */
    public function test_symlinked_zip_is_rejected(): void {
        if (!function_exists('symlink') || PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Requires Unix symlink support.');
        }
        $workspace = $this->archive();
        $other = $this->archive();
        unlink($workspace['directory'] . '/archive.zip');
        symlink($other['directory'] . '/archive.zip', $workspace['directory'] . '/archive.zip');
        $this->expectException(\moodle_exception::class);
        temporary_archive::resolve($workspace['token']);
    }
}
