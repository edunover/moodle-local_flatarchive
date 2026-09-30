<?php
namespace local_flatarchive;

defined('MOODLE_INTERNAL') || die();

use local_flatarchive\archive\temporary_archive;

/**
 * Temporary archive access and cleanup regression tests.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\temporary_archive
 */
final class temporary_archive_test extends \advanced_testcase {
    /** Isolate session and filesystem changes. */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /** @return array Registered fixture archive. */
    private function archive(): array {
        $workspace = temporary_archive::create();
        file_put_contents($workspace['directory'] . '/archive.zip', 'test archive');
        temporary_archive::register($workspace['token'], 42, 'Course.zip');
        return $workspace;
    }

    /** Simultaneous exports remain distinct and cleanup leaves neighbours intact. */
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

    /** A different user is rejected even if session metadata is present. */
    public function test_other_user_cannot_resolve_archive(): void {
        $workspace = $this->archive();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\moodle_exception::class);
        temporary_archive::resolve($workspace['token']);
    }

    /** A known token alone is insufficient outside the owner's session. */
    public function test_unknown_session_cannot_resolve_archive(): void {
        global $SESSION;
        $workspace = $this->archive();
        unset($SESSION->local_flatarchive_archives);
        $this->expectException(\moodle_exception::class);
        temporary_archive::resolve($workspace['token']);
    }

    /** Missing ZIPs are never registered as successful exports. */
    public function test_missing_zip_cannot_be_registered(): void {
        $workspace = temporary_archive::create();
        $this->expectException(\moodle_exception::class);
        temporary_archive::register($workspace['token'], 42, 'Course.zip');
    }

    /** Raw paths cannot be used even with an injected session entry. */
    public function test_path_traversal_is_rejected(): void {
        global $SESSION, $USER;
        $token = '../outside';
        $SESSION->local_flatarchive_archives[$token] = ['userid' => (int)$USER->id];
        $this->expectException(\moodle_exception::class);
        temporary_archive::resolve($token);
    }

    /** A ZIP replaced with a symlink must not expose another temporary file. */
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
