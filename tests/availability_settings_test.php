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

use local_flatarchive\archive\data_settings;
use local_flatarchive\archive\settings_snapshot;

/**
 * Native availability renderables remain readable in offline settings (issue #2).
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\settings_snapshot
 * @covers \local_flatarchive\archive\data_settings
 */
final class availability_settings_test extends \advanced_testcase {
    /**
     * Both section-only and activity exports must preserve restriction logic without source writes.
     *
     * @dataProvider restrictions_provider
     * @param array|null $tree Stored availability tree
     * @param array $labels Expected native operator labels
     * @param array $years Expected dates
     */
    public function test_restrictions_in_settings_exports(?array $tree, array $labels, array $years): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/data/lib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enableavailability', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 1]);
        $page = $generator->create_module('page', ['course' => $course->id, 'section' => 1]);
        $data = $generator->create_module('data', ['course' => $course->id, 'section' => 1]);
        $sectionid = get_fast_modinfo($course)->get_section_info(1)->id;
        $json = $tree === null ? null : json_encode($tree);
        $DB->set_field('course_sections', 'availability', $json, ['id' => $sectionid]);
        foreach ([$page, $data] as $module) {
            $DB->set_field('course_modules', 'availability', $json, ['id' => $module->cmid]);
        }
        rebuild_course_cache($course->id, true);
        $info = get_fast_modinfo($course);
        if ($tree !== null && count($tree['c']) > 1) {
            $availabilityitems = [new \core_availability\info_section($info->get_section_info(1)),
                new \core_availability\info_module($info->get_cm($page->cmid))];
            foreach ($availabilityitems as $availability) {
                $this->assertInstanceOf(\core_availability_multiple_messages::class, $availability->get_full_information());
            }
        }

        // No selected activities: this is the user's Settings-only reproduction.
        $directory = make_request_directory();
        $result = settings_snapshot::add([], $info, $directory);
        $this->assertSame([], $result['items']);
        $this->assertArrayHasKey('course-settings.html', $result['files']);
        $sectionfile = 'section-' . $sectionid . '-settings.html';
        $this->assertArrayHasKey($sectionfile, $result['files']);
        // Ordinary activities and Database use separate settings entry points.
        $result = settings_snapshot::add([$page->cmid => '<li>Page</li>'], $info, $directory);
        $pagefile = 'page-' . $page->cmid . '-settings.html';
        $this->assertArrayHasKey($pagefile, $result['files']);
        $datafile = data_settings::write($info->get_cm($data->cmid), $info, $data, [], '', $directory);

        foreach ([$sectionfile, $pagefile, $datafile] as $filename) {
            $html = file_get_contents($directory . '/' . $filename);
            $dom = new \DOMDocument();
            @$dom->loadHTML($html, LIBXML_NONET);
            $xpath = new \DOMXPath($dom);
            $cells = $xpath->query('//td[@class="flatarchive-settings-access"]');
            $this->assertCount(1, $cells);
            $text = $cells->item(0)->textContent;
            if ($tree === null) {
                $this->assertSame(get_string('none'), $text);
            } else {
                foreach ($labels as $label) {
                    $this->assertStringContainsString(get_string($label, 'availability'), $text);
                }
                foreach ($years as $year) {
                    $this->assertStringContainsString($year, $text);
                }
                if (count($tree['c']) > 1) {
                    $this->assertStringContainsString("\n", $text);
                }
            }
            $this->assertSame(0, $xpath->query('//td[@class="flatarchive-settings-access"]//*')->length);
            $this->assertStringNotContainsString('AVAILABILITY_', $text);
        }
        $this->assertSame($json, $DB->get_field('course_sections', 'availability', ['id' => $sectionid]));
        foreach ([$page, $data] as $module) {
            $this->assertSame($json, $DB->get_field('course_modules', 'availability', ['id' => $module->cmid]));
        }
    }

    /**
     * Plain and composite native restriction trees, including hidden and nested groups.
     *
     * @return array Availability cases
     */
    public static function restrictions_provider(): array {
        $from = ['type' => 'date', 'd' => '>=', 't' => 1907755200];
        $until = ['type' => 'date', 'd' => '<', 't' => 1939291200];
        $later = ['type' => 'date', 'd' => '>=', 't' => 1970913600];
        return [
            'no restriction' => [null, [], []],
            'single restriction' => [['op' => '&', 'showc' => [true], 'c' => [$from]], [], ['2030']],
            'AND' => [['op' => '&', 'showc' => [true, true], 'c' => [$from, $until]],
                ['list_root_and'], ['2030', '2031']],
            'OR' => [['op' => '|', 'show' => true, 'c' => [$from, $until]],
                ['list_root_or'], ['2030', '2031']],
            'hidden OR' => [['op' => '|', 'show' => false, 'c' => [$from, $until]],
                ['list_root_or_hidden'], ['2030', '2031']],
            'nested AND/OR' => [['op' => '&', 'showc' => [true, true],
                'c' => [$from, ['op' => '|', 'c' => [$until, $later]]]],
                ['list_root_and', 'list_or'], ['2030', '2031', '2032']],
        ];
    }

    /**
     * Resolve completion references to readable names without live links or unsafe markup.
     */
    public function test_completion_reference_is_resolved_to_passive_text(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enableavailability', 1);
        set_config('enablecompletion', 1);
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $prerequisite = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id, 'name' => 'Préparation & contrôle', 'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $DB->set_field('course_modules', 'availability', json_encode([
            'op' => '&', 'showc' => [true], 'c' => [
                ['type' => 'completion', 'cm' => $prerequisite->cmid, 'e' => COMPLETION_COMPLETE],
            ],
        ]), ['id' => $page->cmid]);
        rebuild_course_cache($course->id, true);
        $info = get_fast_modinfo($course);
        $directory = make_request_directory();
        $file = settings_snapshot::activity($info->get_cm($page->cmid), $info, $directory, 'index.html');
        $html = file_get_contents($directory . '/' . $file);
        $this->assertStringContainsString('Préparation &amp; contrôle', $html);
        $this->assertStringNotContainsString('AVAILABILITY_', $html);
        $this->assertStringNotContainsString('/mod/page/view.php', $html);
    }
}
