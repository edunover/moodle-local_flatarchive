<?php
// This file is part of Moodle - http://moodle.org/
//
// Download only an archive owned by the current user and session.
// @package local_flatarchive
// @copyright 2026 Edunover
// @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_flatarchive\archive\temporary_archive;

require_login();
require_sesskey();
$token = required_param('token', PARAM_ALPHANUM);
$archive = temporary_archive::resolve($token);
$course = get_course($archive['courseid']);
require_login($course);
require_capability('local/flatarchive:archive', context_course::instance($course->id));

// Keep the session lock until completion to prevent concurrent download/deletion.
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="archive.zip"; filename*=UTF-8\'\'' . rawurlencode($archive['filename']));
header('Content-Length: ' . filesize($archive['path']));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
$sent = readfile($archive['path']);
if ($sent !== false && $sent === filesize($archive['path']) && !connection_aborted()) {
    temporary_archive::delete($token);
}
exit;
