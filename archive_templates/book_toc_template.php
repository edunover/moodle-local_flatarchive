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
 * Render the offline book table of contents.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Génère le HTML de la table des matières d'un Livre (bookshortname/index.html).
 *
 * @param stdClass $course
 * @param string   $booktitle    Titre du Livre (cm->name)
 * @param array    $chapterlinks Tableau de chaînes <li>...</li> (liens relatifs vers chapitres)
 * @param string   $relativeshared Chemin vers shared/ (ex: '../')
 * @param string   $visibilitybadges badges HTML de visibilité du Livre
 * @param int $cmid Book course module ID.
 * @return string HTML complet
 */
function local_flatarchive_render_book_toc_template(
    stdClass $course,
    string $booktitle,
    array $chapterlinks,
    string $relativeshared = '../',
    string $visibilitybadges = '',
    int $cmid = 0
): string {
    $coursetitle = s($course->fullname);
    $footer = get_string('footer_copyright', 'local_flatarchive', local_flatarchive_get_release());
    $backlabel = get_string('backtocourseindex', 'local_flatarchive');
    $sitename    = s($course->shortname ?: get_string('pluginname', 'local_flatarchive'));
    $csspath     = $relativeshared . 'shared/moodle.css';
    $linkshtml   = implode("\n                    ", $chapterlinks);

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
            <p><a class="fa-back-index" href="' . $relativeshared . 'index.html">' . $backlabel . '</a></p>
            <div>
                <h1>' . s($booktitle) . '</h1>
                ' . ($visibilitybadges ? '<div class="fa-vis-block">' . $visibilitybadges . '</div>' : '') . '
                ' . ($cmid ? \local_flatarchive\archive\tags::render('core', 'course_modules', $cmid) : '') . '
                <hr>
                <ul>
                    ' . $linkshtml . '
                </ul>
            </div>
        </main>
        <footer id="page-footer" class="py-3">
            <div class="container">
                <div>' . $footer . ' – ' . $coursetitle . '</div>
            </div>
        </footer>
    </div>
</body>
</html>';
}
