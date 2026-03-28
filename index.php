<?php
// Entry page for local_flatarchive.

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

// $PAGE->requires->js(new moodle_url('/local/flatarchive/js/selectors.js'));
// DEBUG ONLY
$PAGE->requires->js(
    new moodle_url(
        '/local/flatarchive/js/selectors.js',
        ['v' => time()]
    )
);
// END DEBUG
$PAGE->requires->js_init_code(
    'window.FLATARCHIVE = { courseid: ' . (int)$course->id . ' };'
);

echo $OUTPUT->header();

$templatecontext = [
    'courseid' => $course->id,
    'selectall' => get_string('selectall', 'local_flatarchive'),
    'deselectall' => get_string('deselectall', 'local_flatarchive'),
    'archiveselection' => get_string('archiveselection', 'local_flatarchive'),
    'downloadzip' => get_string('downloadzip', 'local_flatarchive'),
    'status_idle' => get_string('status_idle', 'local_flatarchive'),
    'loading' => get_string('loading', 'local_flatarchive'),
];

echo $OUTPUT->render_from_template('local_flatarchive/index', $templatecontext);

echo $OUTPUT->footer();

