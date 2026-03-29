<?php
/**
 * Endpoint AJAX : génération d'un PDF pour un job Flatarchive.
 *
 * Phase 1 :
 * - Un seul module Page
 * - HTML normalisé (icônes + images)
 * - PDF autonome
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../locallib.php');
require_once(__DIR__ . '/../vendor/autoload.php');

require_login();
require_sesskey();

global $DB, $CFG, $PAGE;

// -----------------------------------------------------------------------------
// 1) Paramètre
// -----------------------------------------------------------------------------
$jobid = required_param('jobid', PARAM_INT);
// Initialisation minimale obligatoire pour format_text()
$PAGE->set_context($contextmodule);
$PAGE->set_url(
    new moodle_url('/local/flatarchive/ajax/generate_pdf.php', ['jobid' => $jobid])
);
$PAGE->set_pagelayout('embedded'); // layout neutre, sans chrome

// -----------------------------------------------------------------------------
// 2) Job
// -----------------------------------------------------------------------------
$job = $DB->get_record('local_flatarchive_job', ['id' => $jobid], '*', MUST_EXIST);

$coursecontext = context_course::instance($job->courseid);
require_capability('local/flatarchive:archive', $coursecontext);

// -----------------------------------------------------------------------------
// 3) CMID
// -----------------------------------------------------------------------------
$cmids = json_decode($job->cmids, true);

if (!is_array($cmids) || empty($cmids)) {
    throw new moodle_exception('jobempty', 'local_flatarchive');
}

$cmid = (int)$cmids[0];

// -----------------------------------------------------------------------------
// 4) Module Page
// -----------------------------------------------------------------------------
$cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);

if ((int)$cm->course !== (int)$job->courseid) {
    throw new moodle_exception('invalidcoursemodule', 'error');
}

$contextmodule = context_module::instance($cmid);

// -----------------------------------------------------------------------------
// 5) Job → running
// -----------------------------------------------------------------------------
$job->status = 'running';
$job->timemodified = time();
$DB->update_record('local_flatarchive_job', $job);

// -----------------------------------------------------------------------------
// 6) Données Page
// -----------------------------------------------------------------------------
require_once($CFG->dirroot . '/mod/page/lib.php');

$page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);

// -----------------------------------------------------------------------------
// 7) Titre + contenu
// -----------------------------------------------------------------------------
$title = format_string(
    $page->name,
    true,
    ['context' => $contextmodule, 'escape' => false]
);

$content = format_text(
    $page->content,
    $page->contentformat,
    ['context' => $contextmodule]
);

// IMPORTANT : résolution pluginfile AVANT locallib
$content = file_rewrite_pluginfile_urls(
    $content,
    'pluginfile.php',
    $contextmodule->id,
    'mod_page',
    'content',
    $page->id
);

// -----------------------------------------------------------------------------
// 8) Dossiers
// -----------------------------------------------------------------------------
$basedir   = $CFG->dataroot . '/local_flatarchive';
$jobdir    = $basedir . '/job_' . $jobid;
$assetsdir = $jobdir . '/assets';

if (!is_dir($assetsdir)) {
    mkdir($assetsdir, $CFG->directorypermissions, true);
}

// -----------------------------------------------------------------------------
// 9) Normalisation Flatarchive
// -----------------------------------------------------------------------------
$title   = flatarchive_prepare_html_for_archive($title, $contextmodule, $assetsdir);
$content = flatarchive_prepare_html_for_archive($content, $contextmodule, $assetsdir);

// -----------------------------------------------------------------------------
// 10) PDF
// -----------------------------------------------------------------------------
$tempdir = make_temp_directory('local_flatarchive_mpdf');

$mpdf = new \Mpdf\Mpdf([
    'tempDir' => $tempdir,
    'mode'    => 'utf-8',
    'format'  => 'A4',
]);

$mpdf->WriteHTML('<h1>' . $title . '</h1>');
$mpdf->WriteHTML($content);

$pdfpath = $jobdir . '/page_' . $cmid . '.pdf';
$mpdf->Output($pdfpath, \Mpdf\Output\Destination::FILE);

// -----------------------------------------------------------------------------
// 11) Job → done
// -----------------------------------------------------------------------------
$job->status = 'done';
$job->timemodified = time();
$DB->update_record('local_flatarchive_job', $job);

// -----------------------------------------------------------------------------
// 12) JSON propre
// -----------------------------------------------------------------------------
ob_clean();
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'ok',
    'jobid'  => $jobid,
    'cmid'   => $cmid,
    'pdf'    => basename($pdfpath),
]);

die;
