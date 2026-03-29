<?php
// This file is part of Moodle - http://moodle.org/
//
// Template HTML pour l'index de l'archive (local_flatarchive).

defined('MOODLE_INTERNAL') || die();

/**
 * Génère le HTML de la page index.html de l'archive.
 *
 * @param stdClass $course
 * @param int $courseid
 * @param array $page_links tableau de chaînes <li><a href="...">...</a></li> (liens relatifs)
 * @param string $courseshortname nom court du cours (pour affichage)
 * @return string HTML complet
 */
function local_flatarchive_render_index_template(stdClass $course, int $courseid, array $page_links, string $courseshortname): string {
    $linkshtml = !empty($page_links) ? implode("\n                    ", $page_links) : '';
    $coursetitle = s($course->fullname);
    $sitename = s($course->shortname ?: get_string('pluginname', 'local_flatarchive'));

    return '<!DOCTYPE html>
<html lang="' . current_language() . '">
<head>
    <meta charset="UTF-8">
    <title>' . $sitename . '</title>
    <link rel="stylesheet" type="text/css" href="shared/moodle.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
</head>
<body>
    <div class="d-flex flex-column h-100">
        <nav class="navbar navbar-light bg-white border-bottom">
            <div class="container-fluid p-0">
                <a class="navbar-brand" href="#">' . $sitename . '</a>
            </div>
        </nav>
        <main class="container-fluid mt-2 flex-grow-1 flex-shrink-1">
            <div>
                <h1>' . $coursetitle . '</h1>
                <hr>
                <ul>
                    ' . $linkshtml . '
                </ul>
            </div>
        </main>
        <footer id="page-footer" class="py-3">
            <div class="container">
                <div>' . get_string('footer_copyright', 'local_flatarchive', local_flatarchive_get_release()) . ' – ' . $coursetitle . '</div>
            </div>
        </footer>
    </div>
</body>
</html>';
}
