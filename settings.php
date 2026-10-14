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

/**
 * Institution appearance settings for exported archives.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_flatarchive', get_string('pluginname', 'local_flatarchive'));
    $ADMIN->add('localplugins', $settings);
    if ($ADMIN->fulltree) {
        $choices = [];
        foreach (array_keys(\local_flatarchive\archive\appearance::presets()) as $key) {
            $choices[$key] = get_string('style_' . $key, 'local_flatarchive');
        }
        $settings->add(new admin_setting_configselect(
            'local_flatarchive/stylepreset',
            get_string('stylepreset', 'local_flatarchive'),
            get_string('stylepreset_help', 'local_flatarchive'),
            'none',
            $choices
        ));
        $settings->add(new admin_setting_configtextarea(
            'local_flatarchive/customcss',
            get_string('customcss', 'local_flatarchive'),
            get_string('customcss_help', 'local_flatarchive'),
            '',
            PARAM_RAW,
            80,
            24
        ));
        $PAGE->requires->js_call_amd('local_flatarchive/appearance', 'init', [\local_flatarchive\archive\appearance::presets()]);
    }
}
