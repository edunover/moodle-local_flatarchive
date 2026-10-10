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
 * Generate a static HTML course archive and its download link.
 *
 * @package local_flatarchive
 * @copyright 2025 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/archive_templates/index_template.php');
require_once(__DIR__ . '/archive_templates/about_template.php');
require_once(__DIR__ . '/archive_templates/page_template.php');
require_once(__DIR__ . '/archive_templates/book_toc_template.php');

global $DB;

use local_flatarchive\archive\folder_exporter;
use local_flatarchive\archive\glossary_exporter;
use local_flatarchive\archive\wiki_exporter;
use local_flatarchive\archive\feedback_exporter;
use local_flatarchive\archive\label_exporter;
use local_flatarchive\archive\paths;
use local_flatarchive\archive\resource_exporter;
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
    $supported = ['page', 'book', 'label', 'resource', 'folder', 'glossary', 'wiki', 'feedback'];
    if (!isset($cms[$cmid]) || !in_array($cms[$cmid]->modname, $supported, true)) {
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

    $indexitems = [];
    $zipfiles = [];
    $zipfiles['shared/moodle.css'] = $sharedfolder . '/moodle.css';

    foreach ($cmids as $cmid) {
        $cm = $cms[$cmid];

        if (in_array($cm->modname, ['glossary', 'wiki', 'feedback'], true)) {
            $exporters = ['wiki' => wiki_exporter::class, 'glossary' => glossary_exporter::class,
                'feedback' => feedback_exporter::class];
            $exporter = $exporters[$cm->modname];
            $export = $exporter::render($cm, $modinfo, $tempdir);
            $indexitems[$cmid] = $export['item'];
            foreach ($export['filenames'] as $filename) {
                $zipfiles[$filename] = $tempdir . '/' . $filename;
            }
            $DB->get_field_sql('SELECT 1');
            continue;
        }

        if ($cm->modname === 'folder') {
            $export = folder_exporter::render($cm, $modinfo, $tempdir);
            $indexitems[$cmid] = $export['item'];
            $zipfiles[$export['filename']] = $tempdir . '/' . $export['filename'];
            $DB->get_field_sql('SELECT 1');
            continue;
        }

        if ($cm->modname === 'resource') {
            $indexitems[$cmid] = resource_exporter::render($cm, $modinfo, $tempdir);
            continue;
        }

        if ($cm->modname === 'label') {
            $indexitems[$cmid] = label_exporter::render($cm, $modinfo, $tempdir);
            continue;
        }

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
            $indexitems[$cmid] = '<li>' . $iconimg . '<a href="' . s(rawurlencode($pagefile)) . '">' . s($cm->name) . '</a>'
                . ($badges ? ' ' . $badges : '') . '</li>';
            $zipfiles[$pagefile] = $pagepath;
            $DB->get_field_sql('SELECT 1'); // Keepalive MySQL (wait_timeout=60s sur LWS).
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
            $chapterlinks = [];
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
                    $html = local_flatarchive_render_page_template(
                        $course,
                        null,
                        $chapter->title,
                        $chaptercontent,
                        $relativeshared
                    );
                    local_flatarchive_write_file($chpath, $html);
                    $chbadge = !empty($chapter->hidden)
                        ? ' <span class="fa-vis-badge fa-vis-hidden">'
                            . get_string('visibility_chapter_hidden', 'local_flatarchive') . '</span>'
                        : '';
                    $chapterlinks[] = '<li><a href="' . s(rawurlencode($chfile)) . '">' . s($chapter->title) . '</a>'
                        . $chbadge . '</li>';
                    $zipfiles[$bookshortname . '/' . $chfile] = $chpath;
                    $DB->get_field_sql('SELECT 1'); // Keepalive MySQL (wait_timeout=60s sur LWS).
                }
            } finally {
                $chapters->close();
            }

            // Table des matières du Livre (bookshortname/index.html).
            $tochtml = local_flatarchive_render_book_toc_template($course, $cm->name, $chapterlinks, $relativeshared, $bookbadges);
            local_flatarchive_write_file($bookdir . '/index.html', $tochtml);
            $zipfiles[$bookshortname . '/index.html'] = $bookdir . '/index.html';

            // Une seule entrée dans l'index principal, pointant vers la TOC du Livre.
            $bookiconpath = local_flatarchive_copy_mod_icon('book', $sharedfolder);
            $bookiconimg = $bookiconpath ? '<img class="fa-modicon" src="' . s($bookiconpath) . '" alt=""> ' : '';
            if ($bookiconpath && !isset($zipfiles[$bookiconpath])) {
                $zipfiles[$bookiconpath] = $tempdir . '/' . $bookiconpath;
            }
            $indexitems[$cmid] = '<li>' . $bookiconimg . '<a href="' . s(rawurlencode($bookshortname) . '/index.html')
                . '">' . s($cm->name) . '</a>'
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

    $sections = local_flatarchive_group_index_links($course, $modinfo, $indexitems);
    $indexhtml = local_flatarchive_render_index_template($course, $courseid, $sections, $course->shortname);
    local_flatarchive_write_file($tempdir . '/index.html', $indexhtml);
    $zipfiles['index.html'] = $tempdir . '/index.html';

    $abouthtml = local_flatarchive_render_about_template($course);
    local_flatarchive_write_file($tempdir . '/about.html', $abouthtml);
    $zipfiles['about.html'] = $tempdir . '/about.html';

    $shortname = clean_filename(clean_param($course->shortname, PARAM_FILE)) ?: 'course_' . $courseid;
    $zipname = 'archive_' . $shortname . '_' . date('YmdHi') . '.zip';
    $zippath = $tempdir . '/archive.zip';
    $DB->get_field_sql('SELECT 1'); // Keepalive avant la création du ZIP.
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

// Read the completed ZIP size, not the total size of its uncompressed contents.
clearstatcache(true, $zippath);
$archivesize = filesize($zippath);
$successmessage = $archivesize === false
    ? get_string('archive_success', 'local_flatarchive')
    : get_string('archive_success_size', 'local_flatarchive', display_size($archivesize));

echo $OUTPUT->header();
echo $OUTPUT->heading($successmessage);
echo html_writer::tag('p', get_string('downloadavailability', 'local_flatarchive'));
echo html_writer::div($successmessage, 'alert alert-success');
echo html_writer::tag(
    'p',
    html_writer::link($downloadurl, get_string('archive_success_download', 'local_flatarchive'), ['class' => 'btn btn-primary'])
);
echo html_writer::tag(
    'p',
    html_writer::link(
        new moodle_url('/local/flatarchive/index.php', ['id' => $courseid]),
        get_string('backtoflatarchive', 'local_flatarchive')
    )
);
echo $OUTPUT->footer();
