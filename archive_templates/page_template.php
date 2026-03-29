<?php
// This file is part of Moodle - http://moodle.org/
//
// Template HTML pour une page d'activité (Page ou chapitre Livre) dans l'archive (local_flatarchive).

defined('MOODLE_INTERNAL') || die();

/**
 * Génère le HTML d'une page de contenu (mod_page ou chapitre mod_book).
 *
 * @param stdClass $course
 * @param cm_info $cm (optionnel pour livre, null pour chapitre)
 * @param string $title titre de la page
 * @param string $content contenu HTML déjà formaté (liens fichiers relatifs)
 * @param string $relativeshared chemin relatif vers shared/ (ex: '' ou '../')
 * @param string $visibilitybadges badges HTML de visibilité (généré par local_flatarchive_visibility_badges)
 * @return string HTML complet
 */
function local_flatarchive_render_page_template(stdClass $course, $cm, string $title, string $content, string $relativeshared = '', string $visibilitybadges = ''): string {
    $coursetitle = s($course->fullname);
    $sitename = s($course->shortname ?: get_string('pluginname', 'local_flatarchive'));
    $csspath = $relativeshared . 'shared/moodle.css';

    return '<!DOCTYPE html>
<html lang="' . current_language() . '">
<head>
    <meta charset="UTF-8">
    <title>' . s($title) . '</title>
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
                <h1>' . s($title) . '</h1>
                ' . ($visibilitybadges ? '<div class="fa-vis-block">' . $visibilitybadges . '</div>' : '') . '
                <hr>
                <div class="card">
                    <div class="card-body">' . $content . '</div>
                </div>
            </div>
        </main>
        <footer id="page-footer" class="py-3">
            <div class="container">
                <div>' . get_string('footer_copyright', 'local_flatarchive', get_config('local_flatarchive', 'release')) . ' – ' . $coursetitle . '</div>
            </div>
        </footer>
    </div>
</body>
</html>';
}
