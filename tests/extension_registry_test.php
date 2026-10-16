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

use local_flatarchive\extension\exporter;
use local_flatarchive\extension\registry;

/**
 * Extension boundaries and fail-closed branch checks.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\extension\registry
 */
final class extension_registry_test extends \advanced_testcase {
    /**
     * Construct a controllable extension without installing a commercial plugin.
     * @param string $module Target module
     * @param array $branches Allowed branches
     * @param bool $permitted Access decision
     * @return exporter
     */
    private function exporter(string $module, array $branches, bool $permitted = true): exporter {
        $exporter = $this->createMock(exporter::class);
        $exporter->method('module')->willReturn($module);
        $exporter->method('branches')->willReturn($branches);
        $exporter->method('permitted')->willReturn($permitted);
        $exporter->method('render')->willReturnCallback(static function ($cm, $modinfo, $directory, $options) {
            file_put_contents($directory . '/index.html', '<html><head></head><body>'
                . '<a href="../../index.html">Course</a><script>alert(1)</script></body></html>');
            return ['filename' => 'index.html', 'filenames' => ['index.html']];
        });
        return $exporter;
    }

    /**
     * A whole Moodle branch is allowed, not its neighbour or a loosely typed value.
     */
    public function test_branch_policy_and_native_protection(): void {
        $registry = new registry([$this->exporter('example', [405])]);
        $this->assertTrue($registry->available('example', 405));
        $this->assertFalse($registry->available('example', 500));
        $this->assertFalse($registry->available('unknown', 405));
        $this->assertFalse((new registry([$this->exporter('example', [])]))->available('example', 405));
        $this->assertFalse((new registry([$this->exporter('example', ['405'])]))->available('example', 405));
        $this->assertFalse((new registry([$this->exporter('page', [405])]))->available('page', 405));
        $this->assertTrue(\local_flatarchive\archive\selection::supported('page'));
    }

    /**
     * Duplicate providers never silently replace one another.
     */
    public function test_conflicts_fail_closed(): void {
        $registry = new registry([$this->exporter('example', [405]), $this->exporter('example', [405])]);
        $this->assertFalse($registry->available('example', 405));
        $this->assertSame(get_string('extensionconflict', 'local_flatarchive'), $registry->reason('example'));
    }

    /**
     * The server rejects the export after a branch change, before calling the renderer.
     */
    public function test_disallowed_branch_rejected_before_render(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $this->getMockBuilder(\cm_info::class)->disableOriginalConstructor()->onlyMethods(['__get'])->getMock();
        $original = $modinfo->get_cm($module->cmid);
        $cm->method('__get')->willReturnCallback(static fn($key) => $key === 'modname' ? 'example' : $original->$key);
        // An unknown module needs no third-party dependency in the test installation.
        $exporter = $this->exporter('example', [(int)$CFG->branch + 1]);
        $exporter->expects($this->never())->method('render');
        $registry = new registry([$exporter]);
        $this->assertSame(get_string('extensionbranch', 'local_flatarchive'), $registry->reason('example'));
        $this->expectException(\moodle_exception::class);
        $registry->render($cm, $modinfo, make_request_directory(), []);
    }

    /**
     * Extension access restrictions cannot be bypassed by a forged selection.
     */
    public function test_extension_permissions_are_enforced(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $this->getMockBuilder(\cm_info::class)->disableOriginalConstructor()->onlyMethods(['__get'])->getMock();
        $original = $modinfo->get_cm($module->cmid);
        $cm->method('__get')->willReturnCallback(static fn($key) => $key === 'modname' ? 'example' : $original->$key);
        $registry = new registry([$this->exporter('example', [(int)$CFG->branch], false)]);
        $this->assertFalse($registry->permitted($cm));
        $this->expectException(\moodle_exception::class);
        $registry->render($cm, $modinfo, make_request_directory(), []);
    }

    /**
     * Extension documents participate in normal layout relocation and HTML hardening.
     */
    public function test_export_and_layout(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $this->getMockBuilder(\cm_info::class)->disableOriginalConstructor()->onlyMethods(['__get'])->getMock();
        $original = $modinfo->get_cm($module->cmid);
        $cm->method('__get')->willReturnCallback(static fn($key) => $key === 'modname' ? 'example' : $original->$key);
        $registry = new registry([$this->exporter('example', [(int)$CFG->branch])]);
        $directory = make_request_directory();
        $result = $registry->render($cm, $modinfo, $directory, ['settings']);
        $this->assertSame('extensions/' . $cm->id . '/index.html', $result['filename']);
        file_put_contents($directory . '/index.html', '<html><body>Course</body></html>');
        $files = ['index.html' => $directory . '/index.html'];
        foreach ($result['filenames'] as $filename) {
            $files[$filename] = $directory . '/' . $filename;
        }
        $mapped = \local_flatarchive\archive\layout::organise($files);
        $html = file_get_contents($mapped['resources/' . $result['filename']]);
        $this->assertStringContainsString('../../../index.html', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    /**
     * Filenames cannot escape the extension's assigned directory.
     */
    public function test_traversal_rejected(): void {
        $this->expectException(\coding_exception::class);
        registry::validate_result(['filename' => '../index.html', 'filenames' => ['../index.html']], make_request_directory());
    }

    /**
     * An absolute symlink target must not be added to the archive.
     */
    public function test_external_symlink_rejected(): void {
        $directory = make_request_directory();
        file_put_contents($directory . '/private.html', 'private');
        mkdir($directory . '/export');
        symlink($directory . '/private.html', $directory . '/export/index.html');
        $this->expectException(\coding_exception::class);
        registry::validate_result(['filename' => 'index.html', 'filenames' => ['index.html']], $directory . '/export');
    }
}
