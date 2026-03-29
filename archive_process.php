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
require_once(__DIR__ . '/archive_templates/index_template.php');
require_once(__DIR__ . '/archive_templates/about_template.php');
require_once(__DIR__ . '/archive_templates/page_template.php');
require_once(__DIR__ . '/archive_templates/book_toc_template.php');

global $DB;

$courseid = required_param('id', PARAM_INT);
$cmids = optional_param_array('cmid', [], PARAM_INT);
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

$tempdir = make_temp_directory('flatarchive/' . $courseid . '_' . time());
$sharedfolder = $tempdir . '/shared';
mkdir($sharedfolder, 0755, true);

$stylecss = __DIR__ . '/style/moodle.css';
if (file_exists($stylecss)) {
    copy($stylecss, $sharedfolder . '/moodle.css');
}

$page_links = [];
$zipfiles = [];
$zipfiles['shared/moodle.css'] = $sharedfolder . '/moodle.css';

/**
 * Copie les fichiers du file area du module vers tempdir/files/$cmid/ et réécrit le contenu.
 *
 * @param string $content contenu brut (avec @@PLUGINFILE@@)
 * @param context_module $context
 * @param string $component ex: mod_page, mod_book
 * @param string $filearea ex: content
 * @param int $itemid ex: 0 ou chapterid
 * @param string $tempdir répertoire temporaire racine
 * @param int $cmid id du cm (pour sous-dossier files/$cmid)
 * @param string $relativeprefix préfixe pour les URLs ('' ou '../')
 * @return string contenu avec URLs réécrites en relatifs
 */
function local_flatarchive_copy_pluginfiles_and_rewrite(
    string $content,
    context_module $context,
    string $component,
    string $filearea,
    int $itemid,
    string $tempdir,
    int $cmid,
    string $relativeprefix = ''
): string {
    global $CFG;
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, false, false);
    $filesdir = $tempdir . '/files/' . $cmid;
    if (!is_dir($filesdir)) {
        mkdir($filesdir, 0755, true);
    }
    foreach ($files as $f) {
        if ($f->is_directory()) {
            continue;
        }
        $path = $f->get_filepath() . $f->get_filename();
        $dest = $filesdir . '/' . ltrim($path, '/');
        $fulldir = dirname($dest);
        if (!is_dir($fulldir)) {
            mkdir($fulldir, 0755, true);
        }
        file_put_contents($dest, $f->get_content());
    }
    // Rewrite @@PLUGINFILE@@/ to relative path (files/$cmid/) for static archive.
    // We do not use file_rewrite_pluginfile_urls() as it expects 6+ params and produces absolute URLs.
    $content = str_replace('@@PLUGINFILE@@/', $relativeprefix . 'files/' . $cmid . '/', $content);
    return $content;
}

foreach ($cmids as $cmid) {
    if (!isset($cms[$cmid])) {
        continue;
    }
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
        $shortname = clean_param($cm->name, PARAM_FILE) ?: 'page_' . $cm->id;
        $shortname = clean_filename($shortname);
        if ($shortname === '') {
            $shortname = 'page_' . $cm->id;
        }
        $pagefile = $shortname . '.html';
        $pagepath = $tempdir . '/' . $pagefile;
        $html = local_flatarchive_render_page_template($course, $cm, $cm->name, $pagecontent, '');
        file_put_contents($pagepath, $html);
        $page_links[] = '<li><a href="' . s($pagefile) . '">' . s($cm->name) . '</a></li>';
        $zipfiles[$pagefile] = $pagepath;
        continue;
    }

    if ($cm->modname === 'book') {
        $book = $DB->get_record('book', ['id' => $cm->instance], '*', MUST_EXIST);
        $chapters = $DB->get_records('book_chapters', ['bookid' => $book->id], 'pagenum');
        $bookshortname = clean_param($cm->name, PARAM_FILE) ?: 'book_' . $cm->id;
        $bookshortname = clean_filename($bookshortname);
        if ($bookshortname === '') {
            $bookshortname = 'book_' . $cm->id;
        }
        $bookdir = $tempdir . '/' . $bookshortname;
        if (!is_dir($bookdir)) {
            mkdir($bookdir, 0755, true);
        }
        $relativeshared = '../';
        $pagecontext = context_module::instance($cm->id);
        $chapter_links = [];
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
            $chshortname = clean_param($chapter->title, PARAM_FILE) ?: 'ch_' . $chapter->id;
            $chshortname = clean_filename($chshortname);
            if ($chshortname === '') {
                $chshortname = 'ch_' . $chapter->id;
            }
            $chfile = $chshortname . '.html';
            $chpath = $bookdir . '/' . $chfile;
            $html = local_flatarchive_render_page_template($course, null, $chapter->title, $chaptercontent, $relativeshared);
            file_put_contents($chpath, $html);
            $chapter_links[] = '<li><a href="' . s($chfile) . '">' . s($chapter->title) . '</a></li>';
            $zipfiles[$bookshortname . '/' . $chfile] = $chpath;
        }

        // Table des matières du Livre (bookshortname/index.html).
        $tochtml = local_flatarchive_render_book_toc_template($course, $cm->name, $chapter_links, $relativeshared);
        file_put_contents($bookdir . '/index.html', $tochtml);
        $zipfiles[$bookshortname . '/index.html'] = $bookdir . '/index.html';

        // Une seule entrée dans l'index principal, pointant vers la TOC du Livre.
        $page_links[] = '<li><a href="' . s($bookshortname . '/index.html') . '">' . s($cm->name) . '</a></li>';
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
file_put_contents($tempdir . '/index.html', $indexhtml);
$zipfiles['index.html'] = $tempdir . '/index.html';

$abouthtml = local_flatarchive_render_about_template($course);
file_put_contents($tempdir . '/about.html', $abouthtml);
$zipfiles['about.html'] = $tempdir . '/about.html';

$timestamp = basename($tempdir);
$zipname = 'archive_' . $courseid . '_' . preg_replace('/^.*_/', '', $timestamp) . '.zip';
$zippath = $tempdir . '/' . $zipname;
$packer = get_file_packer('application/zip');
$packer->archive_to_pathname($zipfiles, $zippath, true);

$downloadurl = new moodle_url('/local/flatarchive/serve_temp.php', [
    'path' => 'flatarchive/' . $timestamp . '/' . $zipname,
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
