<?php
// This file is part of Moodle - http://moodle.org/
//
// Template HTML pour la page « À propos » de l'archive (local_flatarchive).

defined('MOODLE_INTERNAL') || die();

/**
 * Génère le HTML de about.html de l'archive.
 *
 * @param stdClass $course
 * @return string HTML complet
 */
function local_flatarchive_render_about_template(stdClass $course): string {
    $coursetitle = s($course->fullname);
    $sitename = s($course->shortname ?: get_string('pluginname', 'local_flatarchive'));
    $summary = '';
    if (!empty($course->summary)) {
        $summary = format_text($course->summary, $course->summaryformat, [
            'context' => context_course::instance($course->id),
            'noclean' => true,
        ]);
    }

    return '<!DOCTYPE html>
<html lang="' . current_language() . '">
<head>
    <meta charset="UTF-8">
    <title>' . $sitename . ' – ' . get_string('about_course', 'local_flatarchive') . '</title>
    <link rel="stylesheet" type="text/css" href="shared/moodle.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
</head>
<body>
    <div class="d-flex flex-column h-100">
        <nav class="navbar navbar-light bg-white border-bottom">
            <div class="container-fluid p-0">
                <a class="navbar-brand" href="index.html">' . $sitename . '</a>
            </div>
        </nav>
        <main class="container-fluid mt-2 flex-grow-1 flex-shrink-1">
            <div>
                <h1>' . $coursetitle . '</h1>
                <hr>
                <div class="card">
                    <div class="card-body">' . $summary . '</div>
                </div>
            </div>
        </main>
        <footer id="page-footer" class="py-3">
            <div class="container">
                <div>' . get_string('footer_copyright', 'local_flatarchive') . ' – ' . $coursetitle . '</div>
            </div>
        </footer>
    </div>
</body>
</html>';
}
