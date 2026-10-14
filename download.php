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
 * Display the existing archive download in the current interface language.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_flatarchive\archive\download_page;

require_login();
$token = required_param('token', PARAM_ALPHANUM);
$PAGE->set_url(download_page::url($token));
try {
    $data = download_page::get($token);
} catch (moodle_exception $error) {
    if ($error->errorcode !== 'filenotfound') {
        throw $error;
    }
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('archiveunavailable', 'local_flatarchive'));
    $PAGE->set_heading(get_string('archiveunavailable', 'local_flatarchive'));
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('archiveunavailable', 'local_flatarchive'));
    echo html_writer::tag('p', get_string('archiveunavailable_help', 'local_flatarchive'));
    echo html_writer::link(new moodle_url('/my/courses.php'), get_string('mycourses'), ['class' => 'btn btn-primary']);
    echo $OUTPUT->footer();
    exit;
}
$course = $data['course'];
require_login($course);
$PAGE->set_context(context_course::instance($course->id));
$PAGE->set_title(get_string('archivegenerated', 'local_flatarchive'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('archivegenerated', 'local_flatarchive'));
echo html_writer::tag('p', get_string('downloadavailability', 'local_flatarchive'));
echo html_writer::div($data['message'], 'alert alert-success');
echo html_writer::tag(
    'p',
    html_writer::link(
        $data['downloadurl'],
        get_string('archive_success_download', 'local_flatarchive'),
        ['class' => 'btn btn-primary']
    )
);
echo html_writer::tag(
    'p',
    html_writer::link(
        new moodle_url('/local/flatarchive/index.php', ['id' => $course->id]),
        get_string('backtoflatarchive', 'local_flatarchive')
    )
);
echo $OUTPUT->footer();
