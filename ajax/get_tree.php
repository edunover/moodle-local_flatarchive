<?php
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/externallib.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);

$context = context_course::instance($courseid);
require_capability('local/flatarchive:archive', $context);

require_sesskey();

use local_flatarchive\external\get_tree;

$result = get_tree::execute($courseid);

// Sortie JSON propre
header('Content-Type: application/json');
echo json_encode($result);
die;
