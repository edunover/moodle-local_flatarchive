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

/**
 * Migration from obsolete processing metadata to the table-free exporter.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::xmldb_local_flatarchive_upgrade
 */
final class upgrade_test extends \advanced_testcase {
    /**
     * Only the obsolete table is removed; course content and downloadable archives survive.
     */
    public function test_remove_obsolete_processing_table(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/local/flatarchive/db/upgrade.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'content' => 'Keep this content']);
        $archive = make_temp_directory('flatarchive-upgrade-test') . '/archive.zip';
        file_put_contents($archive, 'existing archive');
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('local_flatarchive_job');
        $this->assertFalse($dbman->table_exists($table));
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table);
        try {
            $DB->insert_record('local_flatarchive_job', (object)['status' => 'done']);
            set_config('version', 2026101207, 'local_flatarchive');
            $this->assertTrue(xmldb_local_flatarchive_upgrade(2026101207));
            $this->assertFalse($dbman->table_exists($table));
            $this->assertSame('Keep this content', $DB->get_field('page', 'content', ['id' => $page->id]));
            $this->assertSame('existing archive', file_get_contents($archive));
            $this->assertEquals(2026101208, get_config('local_flatarchive', 'version'));
            $this->assertTrue(xmldb_local_flatarchive_upgrade(2026101208));
        } finally {
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }
    }

    /**
     * Older installations without the obsolete table can also be upgraded.
     */
    public function test_upgrade_without_obsolete_table(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/local/flatarchive/db/upgrade.php');
        $this->resetAfterTest();
        set_config('version', 2026101207, 'local_flatarchive');
        $this->assertTrue(xmldb_local_flatarchive_upgrade(2026101207));
        $this->assertFalse($DB->get_manager()->table_exists(new \xmldb_table('local_flatarchive_job')));
    }
}
