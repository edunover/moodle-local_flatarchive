<?php
namespace local_flatarchive;

defined('MOODLE_INTERNAL') || die();

use local_flatarchive\archive\paths;

/**
 * Archive path regression tests.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\paths
 */
final class paths_test extends \basic_testcase {
    /** Same titles, case variants and reserved index names must remain distinct. */
    public function test_resource_names_do_not_collide(): void {
        $names = [
            paths::stem('index', 'page', 1) . '.html',
            paths::stem('index', 'page', 2) . '.html',
            paths::stem('INDEX', 'page', 3) . '.html',
            paths::stem('index', 'book', 1),
            paths::stem('index', 'chapter', 1) . '.html',
        ];
        $this->assertCount(5, array_unique(array_map('strtolower', $names)));
        $this->assertNotContains('index.html', $names);
    }

    /** Titles cannot escape their folder or create Windows-reserved names. */
    public function test_unsafe_and_empty_titles_are_normalised(): void {
        foreach (['../a/b', 'CON', 'NUL', '..', '', '<>:"\\|?*', str_repeat('😀', 200)] as $title) {
            $name = paths::stem($title, 'page', 12);
            $this->assertStringEndsWith('--page-12', $name);
            $this->assertDoesNotMatchRegularExpression('/[<>:"\/\\\\|?*\x00-\x1f]/u', $name);
            $this->assertLessThanOrEqual(49, \core_text::strlen($name));
            $this->assertLessThan(255, strlen($name . '.html'));
        }
    }

    /** Repeated media filenames in different chapters need different directories. */
    public function test_chapter_file_areas_are_isolated(): void {
        $this->assertSame('files/5/chapter/7', paths::assets(5, 'chapter', 7));
        $this->assertNotSame(paths::assets(5, 'chapter', 7), paths::assets(5, 'chapter', 8));
        $this->assertNotSame(paths::assets(5, 'content', 0), paths::assets(6, 'content', 0));
    }

    /** Internal callers cannot accidentally build a traversal path. */
    public function test_invalid_file_area_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        paths::assets(5, '../chapter', 7);
    }
}
