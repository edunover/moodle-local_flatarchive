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

use local_flatarchive\archive\url_exporter;

/**
 * URL archive safety and embedded media coverage.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\url_exporter
 */
final class url_exporter_test extends \advanced_testcase {
    /**
     * Links retain their query, while media is copied without resolving user variables.
     */
    public function test_link_media_and_parameters(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $url = $generator->create_module('url', [
            'course' => $course->id, 'externalurl' => 'https://example.org/path?q=1&lang=fr#part',
            'intro' => '<p>Read <img src="@@PLUGINFILE@@/cover.png" alt="Cover"></p>', 'introformat' => FORMAT_HTML,
        ]);
        $DB->set_field('url', 'parameters', serialize(['email' => 'useremail']), ['id' => $url->id]);
        $context = \context_module::instance($url->cmid);
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'mod_url', 'filearea' => 'intro',
            'itemid' => 0, 'filepath' => '/', 'filename' => 'cover.png',
        ], 'image bytes');
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('flatarchive-url-' . random_string());
        $result = url_exporter::render($modinfo->get_cm($url->cmid), $modinfo, $directory);
        $html = file_get_contents($directory . '/' . $result['filename']);
        $this->assertStringContainsString('https://example.org/path?q=1&amp;lang=fr#part', $html);
        $this->assertStringContainsString('files/' . $url->cmid . '/intro/0/cover.png', $html);
        $this->assertStringNotContainsString('useremail', $html);
        $this->assertStringContainsString(get_string('urlparametersnotice', 'local_flatarchive'), $html);
        $this->assertSame('image bytes', file_get_contents($directory . '/files/' . $url->cmid . '/intro/0/cover.png'));
        $DB->set_field('url', 'externalurl', 'javascript:alert(1)', ['id' => $url->id]);
        url_exporter::render($modinfo->get_cm($url->cmid), $modinfo, $directory);
        $html = file_get_contents($directory . '/' . $result['filename']);
        $this->assertStringNotContainsString('href="javascript:', $html);
    }
}
