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

namespace local_flatarchive\admin;

/**
 * Keep preset data with the CSS control, including the new-settings installation form.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class css_setting extends \admin_setting_configtextarea {
    /**
     * Render the control with its own data and initialise the preset selector.
     * @param mixed $data Current CSS
     * @param string $query Settings search query
     * @return string HTML
     */
    public function output_html($data, $query = '') {
        global $PAGE;
        $PAGE->requires->js_call_amd('local_flatarchive/appearance', 'init');
        $presets = \html_writer::div('', '', [
            'id' => 'flatarchive-presets', 'hidden' => 'hidden',
            'data-presets' => json_encode(\local_flatarchive\archive\appearance::presets()),
        ]);
        return parent::output_html($data, $query) . $presets;
    }
}
