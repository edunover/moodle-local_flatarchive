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

use local_flatarchive\archive\settings_snapshot;

/**
 * Portable configuration, permissions, links and confidential-value exclusions.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\settings_snapshot
 */
final class settings_snapshot_test extends \advanced_testcase {
    /**
     * Exercise every supported module and root/nested/offline document navigation.
     */
    public function test_all_activity_settings_and_course(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/index_template.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('usetags', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Course <test>', 'enablecompletion' => 1, 'summary' => '']);
        $directory = make_temp_directory('flatarchive-settings-' . bin2hex(random_bytes(8)));
        mkdir($directory . '/book dir');
        mkdir($directory . '/files');
        file_put_contents($directory . '/files/original.html', '<h1>Original attachment</h1>');
        $items = [];
        $modules = [];
        $types = ['page', 'book', 'label', 'resource', 'folder', 'glossary', 'wiki',
            'feedback', 'choice', 'forum', 'lesson', 'quiz', 'url', 'assign', 'h5pactivity', 'workshop'];
        foreach ($types as $type) {
            $module = $generator->create_module($type, ['course' => $course->id, 'name' => $type . ' & test']);
            $modules[$type] = $module;
            \core_tag_tag::set_item_tags(
                'core',
                'course_modules',
                $module->cmid,
                \context_module::instance($module->cmid),
                ['settings-tag-' . $type]
            );
            $filename = $type === 'book' ? 'book dir/index.html' : $type . '.html';
            if ($type === 'resource') {
                $filename = 'files/original.html';
            } else {
                file_put_contents($directory . '/' . $filename, '<html><body><h1>Activity</h1></body></html>');
            }
            $items[$module->cmid] = $type === 'label' ? '<li>Text with <a href="https://example.org">link</a></li>' :
                '<li><a href="' . str_replace(' ', '%20', $filename) . '">Activity</a></li>';
        }
        $DB->set_field('quiz', 'password', 'DO-NOT-EXPORT-QUIZ-SECRET', ['id' => $modules['quiz']->id]);
        $DB->set_field('lesson', 'password', 'DO-NOT-EXPORT-LESSON-SECRET', ['id' => $modules['lesson']->id]);
        $DB->set_field('quiz', 'reviewmarks', 0x01000, ['id' => $modules['quiz']->id]);
        $DB->set_field('choice', 'showresults', 2, ['id' => $modules['choice']->id]);
        $DB->set_field('wiki', 'firstpagetitle', '<script>unsafe()</script>', ['id' => $modules['wiki']->id]);
        $DB->set_field('page', 'displayoptions', serialize([
            'printintro' => 1, 'popupwidth' => 654, 'internal' => 'DO-NOT-EXPORT-OPTION',
        ]), ['id' => $modules['page']->id]);
        $DB->set_field('page', 'intro', '<p>Description with image</p><img src="@@PLUGINFILE@@/sample.png" alt="Sample">', [
            'id' => $modules['page']->id,
        ]);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($modules['page']->cmid)->id,
            'component' => 'mod_page', 'filearea' => 'intro', 'itemid' => 0,
            'filepath' => '/', 'filename' => 'sample.png',
        ], 'image fixture');
        $modinfo = get_fast_modinfo($course);
        $result = settings_snapshot::add($items, $modinfo, $directory);
        $this->assertCount(17 + count($result['sectionlinks']), $result['files']);
        foreach ($modules as $type => $module) {
            $filename = $type . '-' . $module->cmid . '-settings.html';
            $this->assertArrayHasKey($filename, $result['files']);
            $this->assertStringContainsString($filename, $result['items'][$module->cmid]);
            $html = file_get_contents($result['files'][$filename]);
            $this->assertStringContainsString('href="index.html"', $html);
            $this->assertStringNotContainsString('[[', $html);
            $this->assertStringNotContainsString('DO-NOT-EXPORT', $html);
            $this->assertStringContainsString('fa-settings-table', $html);
            $this->assertStringContainsString('settings-tag-' . $type, $html);
            $this->assertStringNotContainsString('settings-tag-', $result['items'][$module->cmid]);
            $this->assertStringContainsString('class="fa-settings-link"', $result['items'][$module->cmid]);
            $this->assertStringContainsString('fa-settings-description', $html);
        }
        $this->assertSame('<h1>Original attachment</h1>', file_get_contents($directory . '/files/original.html'));
        $pagesettings = file_get_contents($result['files']['page-' . $modules['page']->cmid . '-settings.html']);
        $this->assertStringContainsString('654', $pagesettings);
        $this->assertStringContainsString('Description with image', $pagesettings);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $pagesettings);
        $imagepath = 'files/' . $modules['page']->cmid . '/intro/0/sample.png';
        $this->assertStringContainsString($imagepath, $pagesettings);
        $this->assertSame('image fixture', file_get_contents($directory . '/' . $imagepath));
        $coursesettings = file_get_contents($result['files']['course-settings.html']);
        $this->assertStringContainsString(get_string('settingsemptyfield', 'local_flatarchive'), $coursesettings);
        $booklink = '../book-' . $modules['book']->cmid . '-settings.html';
        $this->assertStringContainsString($booklink, file_get_contents($directory . '/book dir/index.html'));
        $booksettings = file_get_contents($result['files']['book-' . $modules['book']->cmid . '-settings.html']);
        $this->assertStringContainsString('href="book%20dir/index.html"', $booksettings);
        $wikisettings = file_get_contents($result['files']['wiki-' . $modules['wiki']->cmid . '-settings.html']);
        $this->assertStringContainsString('&lt;script&gt;unsafe()', $wikisettings);
        $this->assertStringNotContainsString('<script>', $wikisettings);
        $choicesettings = file_get_contents($result['files']['choice-' . $modules['choice']->cmid . '-settings.html']);
        $this->assertStringContainsString(get_string('publishafterclose', 'choice'), $choicesettings);
        $quizsettings = file_get_contents($result['files']['quiz-' . $modules['quiz']->cmid . '-settings.html']);
        $dom = new \DOMDocument();
        @$dom->loadHTML($quizsettings);
        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query('//tr[th="Marks"]/td');
        $this->assertCount(4, $rows);
        $this->assertSame(['No', 'Yes', 'No', 'No'], array_map(fn($node) => $node->textContent, iterator_to_array($rows)));
        $index = local_flatarchive_render_index_template($course, $course->id, [], $result['courselink']);
        $this->assertStringContainsString('href="course-settings.html"', $index);
        $this->assertStringContainsString('Course &lt;test&gt;', file_get_contents($result['files']['course-settings.html']));
        $this->assertSame('DO-NOT-EXPORT-QUIZ-SECRET', $DB->get_field('quiz', 'password', ['id' => $modules['quiz']->id]));
        $this->assertSame('2', $DB->get_field('choice', 'showresults', ['id' => $modules['choice']->id]));
    }

    /**
     * Empty editors show a marker while image-only descriptions remain real content.
     */
    public function test_empty_and_media_descriptions(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        foreach (['', '<p><br></p>', '<p>&nbsp;</p>'] as $content) {
            $html = settings_snapshot::description($content, FORMAT_HTML, $context);
            $this->assertStringContainsString(get_string('settingsemptyfield', 'local_flatarchive'), $html);
        }
        $html = settings_snapshot::description('<img src="files/test.png" alt="">', FORMAT_HTML, $context);
        $this->assertStringNotContainsString('fa-settings-empty', $html);
        $this->assertStringContainsString('files/test.png', $html);
    }

    /**
     * Section descriptions, files, restrictions and empty sections survive offline relocation.
     */
    public function test_section_settings_and_index(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/index_template.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enableavailability', 1);
        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $modinfo = get_fast_modinfo($course);
        $sectionid = $modinfo->get_section_info(1)->id;
        $DB->update_record('course_sections', (object)[
            'id' => $sectionid, 'name' => 'Section <one>', 'visible' => 0,
            'summary' => '<p>Section description</p><img src="@@PLUGINFILE@@/diagram.png" alt="Diagram">',
            'summaryformat' => FORMAT_HTML,
            'availability' => json_encode(['op' => '&', 'c' => [
                ['type' => 'date', 'd' => '>=', 't' => 1893456000],
            ], 'showc' => [true]]),
        ]);
        $context = \context_course::instance($course->id);
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'course', 'filearea' => 'section',
            'itemid' => $sectionid, 'filepath' => '/', 'filename' => 'diagram.png',
        ], 'section image fixture');
        rebuild_course_cache($course->id, true);
        $modinfo = get_fast_modinfo($course);
        $directory = make_temp_directory('flatarchive-section-' . bin2hex(random_bytes(8)));
        $result = settings_snapshot::add([], $modinfo, $directory);
        $this->assertCount(3, $result['sectionlinks']);
        $filename = 'section-' . $sectionid . '-settings.html';
        $html = file_get_contents($result['files'][$filename]);
        $this->assertStringContainsString('Section &lt;one&gt;', $html);
        $this->assertStringContainsString('Section description', $html);
        $this->assertStringContainsString('2030', $html);
        $this->assertStringContainsString(get_string('settingshidden', 'local_flatarchive'), $html);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $html);
        $this->assertStringNotContainsString('[[', $html);
        $image = 'files/course-' . $course->id . '/section/' . $sectionid . '/diagram.png';
        $this->assertFileExists($directory . '/' . $image);
        $this->assertStringContainsString($image, $html);
        $emptyid = $modinfo->get_section_info(2)->id;
        $emptyhtml = file_get_contents($result['files']['section-' . $emptyid . '-settings.html']);
        $this->assertStringContainsString(get_string('settingsemptyfield', 'local_flatarchive'), $emptyhtml);
        $sections = local_flatarchive_group_index_links($course, $modinfo, [], $result['sectionlinks']);
        $this->assertCount(3, $sections);
        $this->assertSame([], local_flatarchive_group_index_links($course, $modinfo, []));
        $index = local_flatarchive_render_index_template($course, $course->id, $sections);
        $this->assertStringContainsString('fa-section-settings-link', $index);
        local_flatarchive_write_file($directory . '/index.html', $index);
        $files = $result['files'] + ['index.html' => $directory . '/index.html', $image => $directory . '/' . $image];
        $files = \local_flatarchive\archive\layout::organise($files);
        $this->assertStringContainsString('href="resources/' . $filename . '"', file_get_contents($files['index.html']));
        $html = file_get_contents($files['resources/' . $filename]);
        $this->assertStringContainsString('href="../index.html#section-' . $sectionid . '"', $html);
        $this->assertStringContainsString('flatarchive-section-settings-link', file_get_contents($files['index.html']));
    }

    /**
     * Direct section export also requires course editing permission.
     */
    public function test_section_requires_management_permission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);
        $modinfo = get_fast_modinfo($course);
        $this->expectException(\required_capability_exception::class);
        settings_snapshot::section($modinfo->get_section_info(0), $modinfo, make_temp_directory('flatarchive-section-denied'));
    }

    /**
     * Settings must not leak into an archive generated without management permissions.
     */
    public function test_settings_require_management_permissions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);
        $directory = make_temp_directory('flatarchive-settings-' . bin2hex(random_bytes(8)));
        $items = [$page->cmid => '<li>Page</li>'];
        $result = settings_snapshot::add($items, get_fast_modinfo($course), $directory);
        $this->assertSame($items, $result['items']);
        $this->assertSame([], $result['files']);
        $this->assertSame('', $result['courselink']);
        $this->assertSame([], $result['sectionlinks']);
        $this->expectException(\required_capability_exception::class);
        settings_snapshot::activity(
            get_fast_modinfo($course)->get_cm($page->cmid),
            get_fast_modinfo($course),
            $directory,
            'index.html'
        );
    }
}
