<?php
namespace local_flatarchive;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Real Moodle file-storage copying and ZIP regression test.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::local_flatarchive_copy_pluginfiles_and_rewrite
 */
final class archive_files_test extends \advanced_testcase {
    /** Same-name chapter attachments must survive copying and ZIP creation. */
    public function test_chapter_files_are_copied_without_overwriting(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $book = $this->getDataGenerator()->create_module('book', ['course' => $course->id]);
        $context = \context_module::instance($book->cmid);
        $fs = get_file_storage();
        $dir = make_temp_directory('flatarchive-test-' . bin2hex(random_bytes(8)));
        $zipfiles = [];
        foreach ([101 => 'first image', 102 => 'different image'] as $itemid => $content) {
            $fs->create_file_from_string([
                'contextid' => $context->id, 'component' => 'mod_book', 'filearea' => 'chapter',
                'itemid' => $itemid, 'filepath' => '/nested/', 'filename' => 'image.png',
            ], $content);
            $html = local_flatarchive_copy_pluginfiles_and_rewrite(
                '<img src="@@PLUGINFILE@@/nested/image.png">', $context, 'mod_book', 'chapter',
                $itemid, $dir, (int)$book->cmid, '../'
            );
            $relative = 'files/' . $book->cmid . '/chapter/' . $itemid . '/nested/image.png';
            $this->assertSame('<img src="../' . $relative . '">', $html);
            $this->assertSame($content, file_get_contents($dir . '/' . $relative));
            $zipfiles[$relative] = $dir . '/' . $relative;
        }
        $packer = get_file_packer('application/zip');
        $this->assertTrue($packer->archive_to_pathname($zipfiles, $dir . '/test.zip'));
        $this->assertNotFalse($packer->extract_to_pathname($dir . '/test.zip', $dir . '/unpacked'));
        $this->assertSame('first image', file_get_contents($dir . '/unpacked/files/' . $book->cmid . '/chapter/101/nested/image.png'));
        $this->assertSame('different image', file_get_contents($dir . '/unpacked/files/' . $book->cmid . '/chapter/102/nested/image.png'));
    }
}
