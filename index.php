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
 * Display the course archive selection interface.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);

require_capability('local/flatarchive:archive', $context);



$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/flatarchive/index.php', ['id' => $course->id]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('archivecourse', 'local_flatarchive'));
$PAGE->set_heading($course->fullname);

$PAGE->requires->js_call_amd('local_flatarchive/selectors', 'init', [['courseid' => $course->id]]);

echo $OUTPUT->header();

$templatecontext = [
    'courseid' => $course->id,
    'pluginsettingsurl' => has_capability('moodle/site:config', context_system::instance())
        ? (new moodle_url('/admin/settings.php', ['section' => 'local_flatarchive']))->out(false) : '',
    'pluginsettingslabel' => get_string('pluginsettings', 'local_flatarchive'),
    'courselabel' => get_string('course'),
    'courseoptions' => array_map(
        static fn($key, $label) => ['key' => $key, 'label' => get_string($label, 'local_flatarchive')],
        ['settings', 'participants', 'grades', 'reports'],
        ['archivesettings', 'archiveparticipants', 'coursegrades', 'archivereports']
    ),
    'coursesettingshelp' => get_string('coursesettingshelp', 'local_flatarchive'),
    'archiveurl' => (new moodle_url('/local/flatarchive/archive_process.php'))->out(false),
    'sesskey' => sesskey(),
    'selectall' => get_string('selectall', 'local_flatarchive'),
    'deselectall' => get_string('deselectall', 'local_flatarchive'),
    'archiveselection' => get_string('archiveselection', 'local_flatarchive'),
    'downloadzip' => get_string('downloadzip', 'local_flatarchive'),
    'generate_html_archive' => get_string('generate_html_archive', 'local_flatarchive'),
    'status_idle' => get_string('status_idle', 'local_flatarchive'),
    'loading' => get_string('loading', 'local_flatarchive'),
    'nothingselected' => get_string('nothingselected', 'local_flatarchive'),
    'archive_description' => get_string('archive_description', 'local_flatarchive'),
];

echo $OUTPUT->render_from_template('local_flatarchive/index', $templatecontext);

echo $OUTPUT->footer();
