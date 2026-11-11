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

use local_flatarchive\archive\data_exporter;

/**
 * Database template fidelity and entry access boundaries.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\data_exporter
 * @covers \local_flatarchive\archive\data_settings
 */
final class data_exporter_test extends \advanced_testcase {
    /**
     * Set up independent fixtures.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Create a Database and text field with distinct list/single designs.
     *
     * @param array $options Module overrides
     * @return array Course, module, field, generator
     */
    private function fixture(array $options = []): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $data = $generator->create_module('data', $options + [
            'course' => $course->id, 'approval' => 1,
            'listtemplateheader' => '<table class="catalogue"><tbody>',
            'listtemplate' => '<tr><td>[[Title]]</td><td>##more##</td></tr>',
            'listtemplatefooter' => '</tbody></table>',
            'singletemplate' => '<article class="detail"><h2>[[Title]]</h2>##user## ##tags##</article>',
            'csstemplate' => '.detail { color: navy; }',
        ]);
        $dg = $generator->get_plugin_generator('mod_data');
        $field = $dg->create_field((object)['type' => 'text', 'name' => 'Title'], $data);
        return [$course, $data, $field, $dg];
    }

    /**
     * Export as the current user to a fresh directory.
     *
     * @param \stdClass $course Course
     * @param \stdClass $data Module
     * @return array Directory, manifest, list HTML
     */
    private function export(\stdClass $course, \stdClass $data): array {
        $directory = make_temp_directory('data-export-' . uniqid());
        $info = get_fast_modinfo($course);
        $result = data_exporter::render($info->get_cm($data->cmid), $info, $directory);
        return [$directory, $result, file_get_contents($directory . '/' . $result['filename'])];
    }

    /**
     * Unconfigured templates preserve entries, navigation and settings without changing source data.
     *
     * @dataProvider unconfigured_templates_provider
     * @param string|null $template Stored template value.
     * @param string|null $css Stored stylesheet value.
     * @param bool $withentries Create fields and entries rather than an entirely empty Database.
     * @param bool $includesettings Export settings as well as content.
     */
    public function test_unconfigured_templates(
        ?string $template,
        ?string $css,
        bool $withentries,
        bool $includesettings
    ): void {
        global $DB;
        $entries = [];
        if ($withentries) {
            [$course, $data, $field, $dg] = $this->fixture();
            foreach (['Énergie & archivage', 'Caractères : < > & é à ç'] as $title) {
                $id = $dg->create_entry($data, [$field->field->id => $title]);
                $entries[$id] = $title;
            }
        } else {
            $course = $this->getDataGenerator()->create_course();
            $data = $this->getDataGenerator()->create_module('data', ['course' => $course->id]);
            $this->assertFalse($DB->record_exists('data_fields', ['dataid' => $data->id]));
        }
        $templates = ['listtemplateheader', 'listtemplate', 'listtemplatefooter', 'singletemplate',
            'addtemplate', 'asearchtemplate', 'rsstemplate', 'rsstitletemplate', 'jstemplate'];
        // Persist values after generation so Moodle's defaults cannot mask the SQL NULL case.
        foreach ($templates as $property) {
            $DB->set_field('data', $property, $template, ['id' => $data->id]);
            $this->assertSame($template, $DB->get_field('data', $property, ['id' => $data->id], MUST_EXIST));
        }
        $DB->set_field('data', 'csstemplate', $css, ['id' => $data->id]);
        $this->assertSame($css, $DB->get_field('data', 'csstemplate', ['id' => $data->id], MUST_EXIST));
        $before = $DB->get_record('data', ['id' => $data->id], '*', MUST_EXIST);
        $recordsbefore = $DB->get_records('data_records', ['dataid' => $data->id], 'id');
        $contentconditions = ['fieldid' => $withentries ? $field->field->id : 0];
        $contentsbefore = $DB->get_records('data_content', $contentconditions, 'id');

        $info = get_fast_modinfo($course);
        $directory = make_request_directory();
        $result = data_exporter::render($info->get_cm($data->cmid), $info, $directory, $includesettings);
        $html = file_get_contents($directory . '/' . $result['filename']);
        $this->assertCount(1 + count($entries) + (int)$includesettings, $result['filenames']);
        $cssfile = 'files/' . $data->cmid . '/database.css';
        $this->assertSame($css ?? '', file_get_contents($directory . '/' . $cssfile));
        $this->assertStringContainsString('href="' . $cssfile . '"', $html);
        if (!$withentries) {
            $this->assertStringContainsString(get_string('dataempty', 'local_flatarchive'), $html);
        }
        foreach ($entries as $id => $title) {
            $entryfile = 'data-' . $data->cmid . '-entry-' . $id . '.html';
            $this->assertContains($entryfile, $result['filenames']);
            $this->assertStringContainsString('href="' . $entryfile . '"', $html);
            $detail = file_get_contents($directory . '/' . $entryfile);
            foreach ([$html, $detail] as $page) {
                $this->assertStringContainsString('<dt>Title</dt>', $page);
                $this->assertStringContainsString(s($title), $page);
                $this->assertStringNotContainsString('##more##', $page);
                $this->assertStringNotContainsString('[[Title]]', $page);
                $this->assertStringContainsString('href="' . $cssfile . '"', $page);
            }
            $this->assertStringContainsString('href="' . $result['filename'] . '"', $detail);
            $this->assertStringContainsString('href="index.html"', $detail);
        }
        $settingsfile = 'data-' . $data->cmid . '-settings.html';
        if ($includesettings) {
            $this->assertContains($settingsfile, $result['filenames']);
            $this->assertStringContainsString('href="' . $settingsfile . '"', $html);
            $settings = file_get_contents($directory . '/' . $settingsfile);
            $this->assertStringContainsString(get_string('settingstemplates', 'local_flatarchive'), $settings);
        } else {
            $this->assertFileDoesNotExist($directory . '/' . $settingsfile);
            $this->assertStringNotContainsString($settingsfile, $html);
        }
        $this->assertEquals($before, $DB->get_record('data', ['id' => $data->id], '*', MUST_EXIST));
        $this->assertEquals($recordsbefore, $DB->get_records('data_records', ['dataid' => $data->id], 'id'));
        $this->assertEquals($contentsbefore, $DB->get_records('data_content', $contentconditions, 'id'));
    }

    /**
     * Optional templates and styles found in new and existing Database activities (issue #3).
     *
     * @return array Template, CSS, entries and settings combinations.
     */
    public static function unconfigured_templates_provider(): array {
        return [
            'empty database with NULL templates and CSS' => [null, null, false, true],
            'empty database with empty templates and CSS' => ['', '', false, false],
            'entries with NULL templates and CSS with settings' => [null, null, true, true],
            'entries with NULL templates and CSS without settings' => [null, null, true, false],
            'entries with empty templates and CSS' => ['', '', true, true],
            'entries with whitespace templates' => [" \n\t ", '', true, false],
            'entries with NULL templates and custom CSS' => [null, 'dl { color: navy; }', true, true],
        ];
    }

    /**
     * A missing stylesheet must not discard the existing list and single-entry layouts.
     *
     * @dataProvider unconfigured_css_provider
     * @param string|null $css Stored CSS value.
     */
    public function test_unconfigured_css_preserves_templates(?string $css): void {
        global $DB;
        [$course, $data, $field, $dg] = $this->fixture();
        $id = $dg->create_entry($data, [$field->field->id => 'Styled entry']);
        $DB->set_field('data', 'csstemplate', $css, ['id' => $data->id]);
        $this->assertSame($css, $DB->get_field('data', 'csstemplate', ['id' => $data->id], MUST_EXIST));
        $before = $DB->get_record('data', ['id' => $data->id], '*', MUST_EXIST);
        [$directory, , $html] = $this->export($course, $data);
        $this->assertStringContainsString('class="catalogue"', $html);
        $entryfile = 'data-' . $data->cmid . '-entry-' . $id . '.html';
        $this->assertStringContainsString('href="' . $entryfile . '"', $html);
        $detail = file_get_contents($directory . '/' . $entryfile);
        $this->assertStringContainsString('class="detail"', $detail);
        $this->assertStringContainsString('Styled entry', $detail);
        $this->assertSame('', file_get_contents($directory . '/files/' . $data->cmid . '/database.css'));
        $this->assertEquals($before, $DB->get_record('data', ['id' => $data->id], '*', MUST_EXIST));
    }

    /**
     * Missing CSS representations used by Moodle.
     *
     * @return array Nullable or empty CSS values.
     */
    public static function unconfigured_css_provider(): array {
        return ['NULL CSS' => [null], 'empty CSS' => ['']];
    }

    /**
     * Templates, local links, approval badges and settings are preserved without source writes.
     */
    public function test_templates_pending_entries_and_setting_metadata(): void {
        global $DB;
        [$course, $data, $field, $dg] = $this->fixture(['requiredentriestoview' => 3]);
        $id = $dg->create_entry($data, [$field->field->id => 'Pending entry'], 0, [], ['approved' => false]);
        $before = $DB->get_record('data', ['id' => $data->id]);
        [$directory, $result, $html] = $this->export($course, $data);
        $entryfile = 'data-' . $data->cmid . '-entry-' . $id . '.html';
        $this->assertCount(3, $result['filenames']);
        $this->assertStringContainsString('class="catalogue"', $html);
        $this->assertStringContainsString($entryfile, $html);
        $this->assertStringContainsString(get_string('datanotapproved', 'local_flatarchive'), $html);
        $this->assertStringContainsString(get_string('datarequiredentries', 'local_flatarchive', 3), $html);
        $detail = file_get_contents($directory . '/' . $entryfile);
        $this->assertStringContainsString('class="detail"', $detail);
        $this->assertStringContainsString('Pending entry', $detail);
        $this->assertStringContainsString('href="index.html"', $detail);
        $this->assertStringContainsString(get_string('datanotapproved', 'local_flatarchive'), $detail);
        $this->assertSame('.detail { color: navy; }', file_get_contents($directory . '/files/' . $data->cmid . '/database.css'));
        $this->assertEquals($before, $DB->get_record('data', ['id' => $data->id]));
        $this->assertEquals(0, $DB->get_field('data_records', 'approved', ['id' => $id]));
    }

    /**
     * A student cannot invoke the complete instructor export directly.
     */
    public function test_student_cannot_export_all_entries(): void {
        [$course, $data, $field, $dg] = $this->fixture(['requiredentriestoview' => 5]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $dg->create_entry($data, [$field->field->id => 'Public approved'], 0, [], ['approved' => true]);
        $dg->create_entry($data, [$field->field->id => 'Private pending'], 0, [], ['approved' => false]);
        $this->setUser($student);
        $dg->create_entry($data, [$field->field->id => 'Own pending'], 0, [], ['approved' => false]);
        $this->expectException(\required_capability_exception::class);
        $this->export($course, $data);
    }

    /**
     * An instructor archive includes entries from every separate group and approval state.
     */
    public function test_instructor_exports_all_groups(): void {
        [$course, $data, $field, $dg] = $this->fixture(['groupmode' => SEPARATEGROUPS]);
        $generator = $this->getDataGenerator();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'editingteacher');
        $a = $generator->create_group(['courseid' => $course->id]);
        $b = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $a->id, 'userid' => $student->id]);
        $dg->create_entry($data, [$field->field->id => 'Group A'], $a->id);
        $dg->create_entry($data, [$field->field->id => 'Group B pending'], $b->id, [], ['approved' => false]);
        $this->setUser($student);
        [, , $html] = $this->export($course, $data);
        $this->assertStringContainsString('Group A', $html);
        $this->assertStringContainsString('Group B pending', $html);
        $this->assertStringContainsString(get_string('datanotapproved', 'local_flatarchive'), $html);
    }

    /**
     * Rich-text attachments survive offline and a missing more tag gets a local link.
     */
    public function test_files_and_fallback_navigation(): void {
        global $DB;
        [$course, $data, $field, $dg] = $this->fixture();
        $textarea = $dg->create_field((object)['type' => 'textarea', 'name' => 'Body'], $data);
        $id = $dg->create_entry($data, [$field->field->id => 'Photo', $textarea->field->id => 'Initial']);
        $content = $DB->get_record('data_content', ['recordid' => $id, 'fieldid' => $textarea->field->id]);
        $content->content = '<p><img src="@@PLUGINFILE@@/sample.png" alt="Fixture"></p>';
        $content->content1 = FORMAT_HTML;
        $DB->update_record('data_content', $content);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($data->cmid)->id, 'component' => 'mod_data',
            'filearea' => 'content', 'itemid' => $content->id, 'filepath' => '/', 'filename' => 'sample.png',
        ], 'fixture bytes');
        $DB->set_field('data', 'singletemplate', '[[Body]]', ['id' => $data->id]);
        $DB->set_field('data', 'listtemplate', '<tr><td>[[Title]]</td></tr>', ['id' => $data->id]);
        [$directory, , $html] = $this->export($course, $data);
        $this->assertStringContainsString(get_string('dataviewentry', 'local_flatarchive'), $html);
        $file = 'files/' . $data->cmid . '/content/' . $content->id . '/sample.png';
        $this->assertSame('fixture bytes', file_get_contents($directory . '/' . $file));
        $detail = file_get_contents($directory . '/data-' . $data->cmid . '-entry-' . $id . '.html');
        $this->assertStringContainsString($file, $detail);
        $this->assertStringNotContainsString('pluginfile.php', $detail);
    }

    /**
     * Instructors can archive entries after the learner availability period.
     */
    public function test_instructor_can_export_closed_activity(): void {
        [$course, $data] = $this->fixture(['timeavailableto' => time() - 60]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'editingteacher');
        $this->setUser($student);
        [, $result] = $this->export($course, $data);
        $this->assertCount(2, $result['filenames']);
    }

    /**
     * Comments, tags and static metadata survive while editing controls are removed.
     */
    public function test_comments_tags_and_passive_templates(): void {
        global $DB, $USER;
        set_config('usecomments', 1);
        [$course, $data, $field, $dg] = $this->fixture(['comments' => 1]);
        $id = $dg->create_entry($data, [$field->field->id => 'Commented entry'], 0, ['Archive tag']);
        $DB->insert_record('comments', (object)[
            'contextid' => \context_module::instance($data->cmid)->id,
            'component' => 'mod_data', 'commentarea' => 'database_entry', 'itemid' => $id,
            'content' => 'A readable comment', 'format' => FORMAT_PLAIN,
            'userid' => $USER->id, 'timecreated' => time(),
        ]);
        $DB->set_field(
            'data',
            'singletemplate',
            '[[Title]] ##tags## ##edit## <form><button>Delete</button></form>',
            ['id' => $data->id]
        );
        [$directory] = $this->export($course, $data);
        $html = file_get_contents($directory . '/data-' . $data->cmid . '-entry-' . $id . '.html');
        $this->assertStringContainsString('A readable comment', $html);
        $this->assertStringContainsString('Archive tag', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('<button', $html);
        $this->assertStringNotContainsString('##edit##', $html);
    }

    /**
     * Picture links open local entries and file bytes are available without Moodle.
     */
    public function test_picture_and_file_fields(): void {
        global $DB;
        [$course, $data, $field, $dg] = $this->fixture();
        $id = $dg->create_entry($data, [$field->field->id => 'Files']);
        foreach (['picture' => 'sample.png', 'file' => 'document.pdf'] as $type => $name) {
            $definition = $dg->create_field((object)['type' => $type, 'name' => $type], $data);
            $contentid = $DB->insert_record('data_content', (object)[
                'fieldid' => $definition->field->id, 'recordid' => $id,
                'content' => $name, 'content1' => 'Fixture',
            ]);
            get_file_storage()->create_file_from_string([
                'contextid' => \context_module::instance($data->cmid)->id, 'component' => 'mod_data',
                'filearea' => 'content', 'itemid' => $contentid, 'filepath' => '/', 'filename' => $name,
            ], $type . ' bytes');
        }
        foreach (['listtemplate', 'singletemplate'] as $view) {
            $DB->set_field('data', $view, '<tr><td>[[picture]] [[file]] ##more##</td></tr>', ['id' => $data->id]);
        }
        [$directory, , $html] = $this->export($course, $data);
        $this->assertStringNotContainsString('pluginfile.php', $html);
        $this->assertStringNotContainsString('/mod/data/view.php', $html);
        $this->assertStringContainsString('data-' . $data->cmid . '-entry-' . $id . '.html', $html);
        $this->assertStringContainsString('sample.png', $html);
        $this->assertStringNotContainsString('thumb_sample.png', $html);
        $this->assertStringContainsString('document.pdf', $html);
    }

    /**
     * Disabling course Settings also omits Database's independently generated settings.
     */
    public function test_settings_can_be_omitted(): void {
        [$course, $data] = $this->fixture();
        $info = get_fast_modinfo($course);
        $directory = make_request_directory();
        $result = data_exporter::render($info->get_cm($data->cmid), $info, $directory, false);
        $filename = 'data-' . $data->cmid . '-settings.html';
        $this->assertNotContains($filename, $result['filenames']);
        $this->assertFileDoesNotExist($directory . '/' . $filename);
        $this->assertStringNotContainsString($filename, file_get_contents($directory . '/' . $result['filename']));
    }

    /**
     * A linked settings snapshot documents readable configuration and inert template source.
     */
    public function test_settings_snapshot(): void {
        global $DB;
        [$course, $data, $field, $dg] = $this->fixture([
            'requiredentriestoview' => 4, 'maxentries' => 12, 'groupmode' => SEPARATEGROUPS,
            'completion' => 2, 'completionentries' => 3, 'timeavailablefrom' => 1600000000,
            'jstemplate' => 'const secret = "DO-NOT-EXPORT-JAVASCRIPT";',
        ]);
        $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Research group']);
        $dg->create_field((object)[
            'type' => 'menu', 'name' => 'Category', 'required' => 1, 'param1' => "First choice\nSecond choice",
        ], $data);
        $DB->set_field('data', 'defaultsort', $field->field->id, ['id' => $data->id]);
        $DB->set_field('data', 'config', '{"token":"DO-NOT-EXPORT-CONFIG"}', ['id' => $data->id]);
        $before = $DB->get_record('data', ['id' => $data->id]);
        [$directory, $result, $index] = $this->export($course, $data);
        $filename = 'data-' . $data->cmid . '-settings.html';
        $this->assertContains($filename, $result['filenames']);
        $this->assertStringContainsString('href="' . $filename . '"', $index);
        $html = file_get_contents($directory . '/' . $filename);
        $this->assertStringContainsString('href="data-' . $data->cmid . '.html"', $html);
        $this->assertStringContainsString('href="index.html"', $html);
        $this->assertStringContainsString('Research group', $html);
        $this->assertStringContainsString(get_string('groupsseparate'), $html);
        $this->assertStringContainsString('Category', $html);
        $this->assertStringContainsString('First choice', $html);
        $this->assertStringContainsString('Second choice', $html);
        $this->assertStringContainsString('[[Title]]', $html);
        $this->assertStringContainsString('&lt;article', $html);
        $this->assertStringContainsString('<td>12</td>', $html);
        $this->assertStringContainsString('<td>4</td>', $html);
        $this->assertStringContainsString(s(userdate(1600000000)), $html);
        $this->assertStringNotContainsString('DO-NOT-EXPORT', $html);
        $this->assertStringNotContainsString('database.css', $html);
        $this->assertEquals($before, $DB->get_record('data', ['id' => $data->id]));
    }
}
