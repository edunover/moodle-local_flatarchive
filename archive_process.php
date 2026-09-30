<?php
// This file is part of Moodle - http://moodle.org/
//
// Archive processing: generates HTML pages and ZIP (local_flatarchive).
//
// @package    local_flatarchive
// @copyright  2025 Edunover
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/archive_templates/index_template.php');
require_once(__DIR__ . '/archive_templates/about_template.php');
require_once(__DIR__ . '/archive_templates/page_template.php');
require_once(__DIR__ . '/archive_templates/book_toc_template.php');

global $DB;

use local_flatarchive\archive\paths;
use local_flatarchive\archive\temporary_archive;

$courseid = required_param('id', PARAM_INT);
$cmids = array_values(array_unique(optional_param_array('cmid', [], PARAM_INT)));
require_sesskey();

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/flatarchive:archive', $context);

if (empty($cmids)) {
    throw new moodle_exception('noselection', 'local_flatarchive');
}

$PAGE->set_url(new moodle_url('/local/flatarchive/archive_process.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('processing_archive', 'local_flatarchive'));
$PAGE->set_heading($course->fullname);

$modinfo = get_fast_modinfo($course);
$cms = $modinfo->get_cms();

// Validate the whole selection before creating any files.
foreach ($cmids as $cmid) {
    if (!isset($cms[$cmid]) || !in_array($cms[$cmid]->modname, ['page', 'book'], true)) {
        throw new moodle_exception('invalidselection', 'local_flatarchive');
    }
}
$workspace = temporary_archive::create();
$tempdir = $workspace['directory'];
try {
    $sharedfolder = $tempdir . '/shared';
    mkdir($sharedfolder, 0755, true);

    $stylecss = __DIR__ . '/style/moodle.css';
    if (!copy($stylecss, $sharedfolder . '/moodle.css')) {
        throw new moodle_exception('archivefailed', 'local_flatarchive');
    }

    $page_links = [];
    $zipfiles = [];
    $zipfiles['shared/moodle.css'] = $sharedfolder . '/moodle.css';

    foreach ($cmids as $cmid) {
        $cm = $cms[$cmid];

        if ($cm->modname === 'page') {
            $pageinstance = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
            $pagecontext = context_module::instance($cm->id);
            $rawcontent = $pageinstance->content;
            $rawcontent = local_flatarchive_copy_pluginfiles_and_rewrite(
                $rawcontent,
                $pagecontext,
                'mod_page',
                'content',
                0,
                $tempdir,
                (int) $cm->id,
                ''
            );
            $pagecontent = format_text($rawcontent, $pageinstance->contentformat, ['context' => $pagecontext]);
            $pagefile = paths::stem($cm->name, 'page', (int)$cm->id) . '.html';
            $pagepath = $tempdir . '/' . $pagefile;
            $badges = local_flatarchive_visibility_badges($cm, $modinfo);
            $html = local_flatarchive_render_page_template($course, $cm, $cm->name, $pagecontent, '', $badges);
            local_flatarchive_write_file($pagepath, $html);
            $iconpath = local_flatarchive_copy_mod_icon('page', $sharedfolder);
            $iconimg = $iconpath ? '<img class="fa-modicon" src="' . s($iconpath) . '" alt=""> ' : '';
            if ($iconpath && !isset($zipfiles[$iconpath])) {
                $zipfiles[$iconpath] = $tempdir . '/' . $iconpath;
            }
            $page_links[] = '<li>' . $iconimg . '<a href="' . s(rawurlencode($pagefile)) . '">' . s($cm->name) . '</a>'
                . ($badges ? ' ' . $badges : '') . '</li>';
            $zipfiles[$pagefile] = $pagepath;
            $DB->get_field_sql('SELECT 1'); // keepalive MySQL (wait_timeout=60s sur LWS).
            continue;
        }

        if ($cm->modname === 'book') {
            $bookshortname = paths::stem($cm->name, 'book', (int)$cm->id);
            $bookdir = $tempdir . '/' . $bookshortname;
            if (!is_dir($bookdir)) {
                mkdir($bookdir, 0755, true);
            }
            $relativeshared = '../';
            $pagecontext = context_module::instance($cm->id);
            $bookbadges = local_flatarchive_visibility_badges($cm, $modinfo);
            $chapter_links = [];
            $chapters = $DB->get_recordset('book_chapters', ['bookid' => $cm->instance], 'pagenum, id');
            try {
                foreach ($chapters as $chapter) {
                    $rawcontent = $chapter->content;
                    $rawcontent = local_flatarchive_copy_pluginfiles_and_rewrite(
                        $rawcontent,
                        $pagecontext,
                        'mod_book',
                        'chapter',
                        (int) $chapter->id,
                        $tempdir,
                        (int) $cm->id,
                        $relativeshared
                    );
                    $chaptercontent = format_text($rawcontent, $chapter->contentformat, ['context' => $pagecontext]);
                    $chfile = paths::stem($chapter->title, 'chapter', (int)$chapter->id) . '.html';
                    $chpath = $bookdir . '/' . $chfile;
                    $html = local_flatarchive_render_page_template($course, null, $chapter->title, $chaptercontent, $relativeshared);
                    local_flatarchive_write_file($chpath, $html);
                    $chbadge = !empty($chapter->hidden)
                        ? ' <span class="fa-vis-badge fa-vis-hidden">'
                            . get_string('visibility_chapter_hidden', 'local_flatarchive') . '</span>'
                        : '';
                    $chapter_links[] = '<li><a href="' . s(rawurlencode($chfile)) . '">' . s($chapter->title) . '</a>'
                        . $chbadge . '</li>';
                    $zipfiles[$bookshortname . '/' . $chfile] = $chpath;
                    $DB->get_field_sql('SELECT 1'); // keepalive MySQL (wait_timeout=60s sur LWS).
                }

            } finally {
                $chapters->close();
            }

            // Table des matières du Livre (bookshortname/index.html).
            $tochtml = local_flatarchive_render_book_toc_template($course, $cm->name, $chapter_links, $relativeshared, $bookbadges);
            local_flatarchive_write_file($bookdir . '/index.html', $tochtml);
            $zipfiles[$bookshortname . '/index.html'] = $bookdir . '/index.html';

            // Une seule entrée dans l'index principal, pointant vers la TOC du Livre.
            $bookiconpath = local_flatarchive_copy_mod_icon('book', $sharedfolder);
            $bookiconimg = $bookiconpath ? '<img class="fa-modicon" src="' . s($bookiconpath) . '" alt=""> ' : '';
            if ($bookiconpath && !isset($zipfiles[$bookiconpath])) {
                $zipfiles[$bookiconpath] = $tempdir . '/' . $bookiconpath;
            }
            $page_links[] = '<li>' . $bookiconimg . '<a href="' . s(rawurlencode($bookshortname) . '/index.html') . '">' . s($cm->name) . '</a>'
                . ($bookbadges ? ' ' . $bookbadges : '') . '</li>';
            continue;
        }
    }

    // Fichiers dans files/ (images, etc.) à inclure dans le ZIP.
    $filesdir = $tempdir . '/files';
    if (is_dir($filesdir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($filesdir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $rel = str_replace($tempdir . '/', '', $item->getPathname());
                $zipfiles[$rel] = $item->getPathname();
            }
        }
    }

    $indexhtml = local_flatarchive_render_index_template($course, $courseid, $page_links, $course->shortname);
    local_flatarchive_write_file($tempdir . '/index.html', $indexhtml);
    $zipfiles['index.html'] = $tempdir . '/index.html';

    $abouthtml = local_flatarchive_render_about_template($course);
    local_flatarchive_write_file($tempdir . '/about.html', $abouthtml);
    $zipfiles['about.html'] = $tempdir . '/about.html';

    $shortname = clean_filename(clean_param($course->shortname, PARAM_FILE)) ?: 'course_' . $courseid;
    $zipname = 'archive_' . $shortname . '_' . date('YmdHi') . '.zip';
    $zippath = $tempdir . '/archive.zip';
    $DB->get_field_sql('SELECT 1'); // keepalive avant la création du ZIP.
    $packer = get_file_packer('application/zip');
    if (!$packer->archive_to_pathname($zipfiles, $zippath, true)) {
        throw new moodle_exception('archivefailed', 'local_flatarchive');
    }
    temporary_archive::register($workspace['token'], $courseid, $zipname);

} catch (Throwable $error) {
    fulldelete($tempdir);
    throw $error;
}

$downloadurl = new moodle_url('/local/flatarchive/serve_temp.php', [
    'token' => $workspace['token'],
    'sesskey' => sesskey(),
]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('archive_success', 'local_flatarchive'));
echo html_writer::div(get_string('archive_success', 'local_flatarchive'), 'alert alert-success');
echo html_writer::tag('p',
    html_writer::link($downloadurl, get_string('archive_success_download', 'local_flatarchive'), ['class' => 'btn btn-primary'])
);
echo html_writer::tag('p',
    html_writer::link(new moodle_url('/local/flatarchive/index.php', ['id' => $courseid]), get_string('flatarchive', 'local_flatarchive'))
);
echo $OUTPUT->footer();
