<?php
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

$courseid = required_param('courseid', PARAM_INT);
$cmids = required_param_array('cmids', PARAM_INT);

$context = context_course::instance($courseid);
require_capability('local/flatarchive:archive', $context);

if (empty($cmids)) {
    throw new moodle_exception('noselection', 'local_flatarchive');
}

global $DB, $USER;

$record = (object)[
    'courseid' => $courseid,
    'userid' => $USER->id,
    'cmids' => json_encode(array_values($cmids)),
    'status' => 'pending',
    'timecreated' => time(),
    'timemodified' => time(),
];

$jobid = $DB->insert_record('local_flatarchive_job', $record);

echo json_encode([
    'status' => 'ok',
    'jobid' => $jobid,
]);
