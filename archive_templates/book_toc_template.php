<?php
// This file is part of Moodle - http://moodle.org/
//
// Template HTML pour la table des matières d'un Livre dans l'archive (local_flatarchive).

defined('MOODLE_INTERNAL') || die();

/**
 * Génère le HTML de la table des matières d'un Livre (bookshortname/index.html).
 *
 * @param stdClass $course
 * @param string   $booktitle    Titre du Livre (cm->name)
 * @param array    $chapter_links Tableau de chaînes <li>...</li> (liens relatifs vers chapitres)
 * @param string   $relativeshared Chemin vers shared/ (ex: '../')
 * @param string   $visibilitybadges badges HTML de visibilité du Livre
 * @return string HTML complet
 */
function local_flatarchive_render_book_toc_template(
    stdClass $course,
    string $booktitle,
    array $chapter_links,
    string $relativeshared = '../',
    string $visibilitybadges = ''
): string {
    $coursetitle = s($course->fullname);
    $sitename    = s($course->shortname ?: get_string('pluginname', 'local_flatarchive'));
    $csspath     = $relativeshared . 'shared/moodle.css';
    $linkshtml   = implode("\n                    ", $chapter_links);

    return '<!DOCTYPE html>
<html lang="' . current_language() . '">
<head>
    <meta charset="UTF-8">
    <title>' . s($booktitle) . '</title>
    <link rel="stylesheet" type="text/css" href="' . $csspath . '">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
</head>
<body>
    <div class="d-flex flex-column h-100">
        <nav class="navbar navbar-light bg-white border-bottom">
            <div class="container-fluid p-0">
                <a class="navbar-brand" href="' . $relativeshared . 'index.html">' . $sitename . '</a>
            </div>
        </nav>
        <main class="container-fluid mt-2 flex-grow-1 flex-shrink-1">
            <div>
                <h1>' . s($booktitle) . '</h1>
                ' . ($visibilitybadges ? '<div class="fa-vis-block">' . $visibilitybadges . '</div>' : '') . '
                <hr>
                <ul>
                    ' . $linkshtml . '
                </ul>
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
