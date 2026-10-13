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
 * Serve a session-owned archive repeatedly during its availability window.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_flatarchive\archive\temporary_archive;

require_login();
require_sesskey();
$token = required_param('token', PARAM_ALPHANUM);
try {
    $archive = temporary_archive::resolve($token);
} catch (moodle_exception $error) {
    if ($error->errorcode !== 'filenotfound') {
        throw $error;
    }
    $PAGE->set_url(new moodle_url('/local/flatarchive/serve_temp.php'));
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
$course = get_course($archive['courseid']);
require_login($course);
require_capability('local/flatarchive:archive', context_course::instance($course->id));

// Serve only the resolved ZIP; never accept a client-provided filesystem path.
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="archive.zip"; filename*=UTF-8\'\'' . rawurlencode($archive['filename']));
header('Content-Length: ' . filesize($archive['path']));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
// Retain the ZIP for retries. Moodle's scheduled temporary-file cleanup reclaims it.
readfile($archive['path']);
exit;
