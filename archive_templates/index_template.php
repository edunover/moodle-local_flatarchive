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
 * Render the offline course index.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Génère le HTML de la page index.html de l'archive.
 *
 * @param stdClass $course
 * @param int $courseid
 * @param array $sections Sections ordonnées avec nom, ID et liens relatifs rendus.
 * @param string $settingslink Optional local settings link
 * @return string HTML complet
 */
function local_flatarchive_render_index_template(
    stdClass $course,
    int $courseid,
    array $sections,
    string $settingslink = ''
): string {
    $sectionshtml = '';
    foreach ($sections as $section) {
        $headingid = 'section-' . (int)$section['id'];
        $badge = array_key_exists('visible', $section) && !$section['visible'] ?
            ' <span class="fa-vis-badge fa-vis-section">' .
                get_string('visibility_section_hidden', 'local_flatarchive') . '</span>' : '';
        $sectionshtml .= '<section class="fa-course-section" aria-labelledby="' . $headingid . '">'
            . '<h2 id="' . $headingid . '">' . s($section['name']) . $badge . '</h2>'
            . '<ul>' . implode("\n", $section['links']) . '</ul></section>';
    }
    $coursetitle = s($course->fullname);
    $footer = get_string('footer_copyright', 'local_flatarchive', local_flatarchive_get_release());
    $backlabel = get_string('backtocourseindex', 'local_flatarchive');
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
                ' . $settingslink . '
                ' . \local_flatarchive\archive\tags::render('core', 'course', $courseid) . '
                <hr>
                ' . $sectionshtml . '
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
