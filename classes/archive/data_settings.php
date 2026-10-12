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

namespace local_flatarchive\archive;

/**
 * Human-readable, allowlisted Database configuration snapshot.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class data_settings {
    /**
     * Write the settings page using the archive stylesheet, never the activity's custom CSS.
     *
     * @param \cm_info $cm Module
     * @param \course_modinfo $modinfo Course information
     * @param \stdClass $data Database configuration
     * @param array $fields Field definitions
     * @param string $intro Introduction with local files
     * @param string $directory Archive directory
     * @return string Filename
     */
    public static function write(
        \cm_info $cm,
        \course_modinfo $modinfo,
        \stdClass $data,
        array $fields,
        string $intro,
        string $directory
    ): string {
        global $DB;
        $context = \context_module::instance($cm->id);
        require_capability('mod/data:manageentries', $context);
        $module = $DB->get_record('course_modules', ['id' => $cm->id], '*', MUST_EXIST);
        $body = settings_snapshot::description($intro, (int)$data->introformat, $context);
        $body .= \html_writer::tag('p', \html_writer::link('data-' . $cm->id . '.html', self::label('backtodata')));
        $body .= \html_writer::tag('p', self::label('settingssnapshot') . ' ' . s(userdate(time())));
        $body .= \html_writer::tag('p', self::label('settingsscope'));
        $rows = [get_string('name') => $data->name];
        // Use explicit strings instead of serializing arbitrary instance/plugin configuration.
        $rows[self::label('settingsvisibility')] = !$cm->visible ? self::label('settingshidden') :
            (empty($cm->visibleoncoursepage) ? self::label('settingsstealth') : self::label('settingsvisible'));
        $rows[self::label('settingsshowdescription')] = self::yes($module->showdescription);
        $groupmode = groups_get_activity_groupmode($cm);
        $rows[get_string('groupmode')] = get_string(['groupsnone', 'groupsseparate', 'groupsvisible'][$groupmode]);
        $rows[self::label('settingsgroupforced')] = self::yes($modinfo->get_course()->groupmodeforce);
        $rows[get_string('grouping', 'group')] = $module->groupingid ?
            (string)$DB->get_field('groupings', 'name', ['id' => $module->groupingid]) : get_string('none');
        $groups = groups_get_all_groups($cm->course, 0, $module->groupingid);
        $rows[get_string('groups')] = $groups ? implode(', ', array_column($groups, 'name')) : get_string('none');
        $availability = new \core_availability\info_module($cm);
        $rows[self::label('settingsaccess')] = trim(strip_tags($availability->get_full_information())) ?: get_string('none');
        $rows[self::label('settingscompletion')] = self::label('settingscompletion' . (int)$module->completion);
        $rows[self::label('settingscompletionview')] = self::yes($module->completionview);
        $rows[self::label('settingscompletiongrade')] = self::yes($module->completiongradeitemnumber !== null);
        $rows[self::label('settingscompletionexpected')] = self::date((int)$module->completionexpected);
        $booleans = ['approval' => 'requireapproval', 'manageapproved' => 'manageapproved', 'comments' => 'allowcomments'];
        foreach ($booleans as $key => $label) {
            $rows[get_string($label, 'data')] = self::yes($data->$key);
        }
        foreach (['requiredentries', 'requiredentriestoview', 'maxentries', 'completionentries', 'rssarticles'] as $key) {
            $rows[self::label('settings' . $key)] = $key === 'maxentries' && !$data->$key ?
                get_string('unlimited') : (string)$data->$key;
        }
        $dates = [
            'timeavailablefrom' => 'availablefromdate', 'timeavailableto' => 'availabletodate',
            'timeviewfrom' => 'viewfromdate', 'timeviewto' => 'viewtodate',
        ];
        foreach ($dates as $key => $label) {
            $rows[get_string($label, 'data')] = self::date((int)$data->$key);
        }
        $sortnames = [DATA_TIMEADDED => self::label('settingstimecreated'),
            DATA_TIMEMODIFIED => self::label('settingstimemodified'),
            DATA_FIRSTNAME => get_string('firstname'), DATA_LASTNAME => get_string('lastname'),
            DATA_APPROVED => self::label('settingsapprovalstatus'), DATA_TAGS => get_string('tags')];
        $rows[self::label('settingssort')] = $fields[$data->defaultsort]->name ??
            ($sortnames[$data->defaultsort] ?? get_string('default'));
        $direction = $data->defaultsortdir ? 'settingsdescending' : 'settingsascending';
        $rows[self::label('settingssortdirection')] = self::label($direction);
        $rows[self::label('settingsrating')] = self::label('settingsrating' . (int)$data->assessed);
        $rows[self::label('settingsscale')] = $data->scale < 0 ?
            (string)$DB->get_field('scale', 'name', ['id' => -$data->scale]) : (string)$data->scale;
        $rows[self::label('settingsratingfrom')] = self::date((int)$data->assesstimestart);
        $rows[self::label('settingsratingto')] = self::date((int)$data->assesstimefinish);
        $grade = $DB->get_record('grade_items', [
            'itemtype' => 'mod', 'itemmodule' => 'data', 'iteminstance' => $data->id, 'itemnumber' => 0,
        ]);
        if ($grade) {
            $rows[self::label('settingsgradepass')] = format_float($grade->gradepass);
            $category = $DB->get_field('grade_categories', 'fullname', ['id' => $grade->categoryid]);
            $rows[self::label('settingsgradecategory')] = $category === '?' ? $modinfo->get_course()->fullname : (string)$category;
        }
        $body .= self::table($rows);
        $body .= \html_writer::tag('h2', self::label('settingsfields'));
        foreach ($fields as $field) {
            $component = 'datafield_' . $field->type;
            $type = get_string_manager()->string_exists('pluginname', $component) ?
                get_string('pluginname', $component) : $field->type;
            $body .= \html_writer::tag('h3', s($field->name));
            $body .= self::table([
                self::label('settingsfieldtype') => $type,
                get_string('description') => $field->description,
                self::label('settingsfieldrequired') => self::yes($field->required),
            ]);
            if (in_array($field->type, ['checkbox', 'menu', 'multimenu', 'radiobutton'], true)) {
                $body .= self::table([self::label('settingsfieldoptions') => $field->param1]);
            }
            $parameters = [
                'text' => ['param1' => 'settingsautolink'],
                'textarea' => ['param1' => 'settingsautolink', 'param2' => 'settingscolumns',
                    'param3' => 'settingsrows', 'param5' => 'settingsmaxbytes'],
                'picture' => ['param1' => 'settingswidth', 'param2' => 'settingsheight',
                    'param3' => 'settingsmaxbytes', 'param4' => 'settingsthumbwidth', 'param5' => 'settingsthumbheight'],
                'file' => ['param3' => 'settingsmaxbytes'],
                'url' => ['param1' => 'settingsautolink', 'param2' => 'settingslinktext', 'param3' => 'settingsnewwindow'],
            ];
            $options = [];
            foreach ($parameters[$field->type] ?? [] as $key => $label) {
                $value = (string)($field->$key ?? '');
                if (in_array($label, ['settingsautolink', 'settingsnewwindow'], true)) {
                    $value = self::yes($value);
                }
                $options[self::label($label)] = $value;
            }
            $body .= self::table($options);
        }
        $body .= \html_writer::tag('h2', self::label('settingstemplates'));
        $templates = ['listtemplateheader', 'listtemplate', 'listtemplatefooter', 'singletemplate',
            'addtemplate', 'asearchtemplate', 'rsstemplate', 'rsstitletemplate', 'csstemplate'];
        foreach ($templates as $key) {
            $body .= \html_writer::tag('h3', self::label('settings' . $key));
            $body .= \html_writer::tag('pre', s((string)($data->$key ?? '')), ['class' => 'fa-settings-source']);
        }
        $body .= self::table([self::label('settingsjavascript') => self::yes(!empty($data->jstemplate))]);
        $filename = 'data-' . $cm->id . '-settings.html';
        $html = \local_flatarchive_render_page_template(
            $modinfo->get_course(),
            $cm,
            $cm->name . ' — ' . self::label('archivesettings'),
            $body
        );
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        return $filename;
    }

    /**
     * Escape all configuration values, including HTML templates and multiline options.
     * @param array $rows Labels and plain values
     * @return string HTML table
     */
    private static function table(array $rows): string {
        $body = '';
        foreach ($rows as $label => $value) {
            $body .= \html_writer::tag('tr', \html_writer::tag('th', s($label), ['scope' => 'row']) .
                \html_writer::tag('td', nl2br(s((string)$value))));
        }
        return $body ? \html_writer::tag('table', \html_writer::tag('tbody', $body), ['class' => 'fa-settings-table']) : '';
    }

    /**
     * Translate an archive label.
     * @param string $key String identifier
     * @return string Translation
     */
    private static function label(string $key): string {
        return get_string($key, 'local_flatarchive');
    }

    /**
     * Format a boolean setting.
     * @param mixed $value Setting value
     * @return string Translated value
     */
    private static function yes($value): string {
        return get_string($value ? 'yes' : 'no');
    }

    /**
     * Format an optional date.
     * @param int $value Timestamp
     * @return string Translated date or disabled indication
     */
    private static function date(int $value): string {
        return $value ? userdate($value) : self::label('settingscompletion0');
    }
}
