<?php
// This file is part of Moodle - https://moodle.org/
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
 * Shared archive rendering and file helpers.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Génère les badges de visibilité HTML pour un module de cours.
 *
 * États couverts :
 * - visible=0                          → badge "caché"
 * - visibleoncoursepage=0, visible=1   → badge "caché de la page de cours" (stealth)
 * - section parente visible=0          → badge "section cachée"
 *
 * @param cm_info          $cm
 * @param course_modinfo   $modinfo
 * @return string HTML des badges (vide si tout est visible)
 */
function local_flatarchive_visibility_badges(cm_info $cm, course_modinfo $modinfo): string {
    $badges = [];

    if (!$cm->visible) {
        $badges[] = '<span class="fa-vis-badge fa-vis-hidden">'
            . get_string('visibility_hidden', 'local_flatarchive') . '</span>';
    } else if ($cm->is_stealth()) {
        $badges[] = '<span class="fa-vis-badge fa-vis-stealth">'
            . get_string('visibility_stealth', 'local_flatarchive') . '</span>';
    }

    $section = $modinfo->get_section_info($cm->sectionnum);
    if ($section && !$section->visible) {
        $badges[] = '<span class="fa-vis-badge fa-vis-section">'
            . get_string('visibility_section_hidden', 'local_flatarchive') . '</span>';
    }

    return implode(' ', $badges);
}

/**
 * Retourne la chaîne de release du plugin (ex: '0.1.0') lue depuis version.php.
 * Fallback sur le numéro de version numérique si absent.
 *
 * @return string
 */
function local_flatarchive_get_release(): string {
    static $result = null;
    if ($result !== null) {
        return $result;
    }
    $plugin = new stdClass();
    include(__DIR__ . '/version.php');
    $release = $plugin->release ?? '';
    $build   = $plugin->version ?? get_config('local_flatarchive', 'version');
    $result = $release ? $release . ' (' . $build . ')' : (string) $build;
    return $result;
}

/**
 * Copie l'icône d'un module Moodle (SVG ou PNG) dans shared/icons/ et retourne
 * le chemin relatif depuis la racine de l'archive.
 *
 * @param string $modname      Nom du module (ex: 'page', 'book')
 * @param string $sharedfolder Chemin absolu vers shared/
 * @return string Chemin relatif (ex: 'shared/icons/page.svg') ou '' si absent
 */
function local_flatarchive_copy_mod_icon(string $modname, string $sharedfolder): string {
    global $CFG;
    $icondir = $sharedfolder . '/icons';
    if (!is_dir($icondir)) {
        mkdir($icondir, 0755, true);
    }
    // Moodle 4.x uses monologo.svg; older versions used icon.svg / icon.png.
    foreach (['monologo.svg', 'icon.svg', 'icon.png'] as $filename) {
        $src = $CFG->dirroot . '/mod/' . $modname . '/pix/' . $filename;
        if (file_exists($src)) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $dest = $icondir . '/' . $modname . '.' . $ext;
            if (!file_exists($dest)) {
                copy($src, $dest);
            }
            return 'shared/icons/' . $modname . '.' . $ext;
        }
    }
    return '';
}

/**
 * Copie les fichiers du file area du module vers tempdir/files/$cmid/$filearea/$itemid/ et réécrit le contenu.
 *
 * @param string $content contenu brut (avec @@PLUGINFILE@@)
 * @param context $context Contexte autorisé du cours ou de l’activité
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
    context $context,
    string $component,
    string $filearea,
    int $itemid,
    string $tempdir,
    int $cmid,
    string $relativeprefix = ''
): string {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, $component, $filearea, $itemid, 'id', false);
    $relativepath = \local_flatarchive\archive\paths::assets($cmid, $filearea, $itemid);
    if ($context instanceof context_course) {
        $relativepath = preg_replace('~^files/~', 'files/course-', $relativepath);
    }
    $filesdir = $tempdir . '/' . $relativepath;
    if (!is_dir($filesdir)) {
        mkdir($filesdir, 0755, true);
    }
    foreach ($files as $f) {
        if ($f->is_directory()) {
            continue;
        }
        $path = $f->get_filepath() . $f->get_filename();
        $relativefile = ltrim($path, '/');
        if (!\local_flatarchive\archive\paths::relative($relativefile)) {
            throw new moodle_exception('archivefailed', 'local_flatarchive');
        }
        $dest = $filesdir . '/' . $relativefile;
        $fulldir = dirname($dest);
        if (!is_dir($fulldir)) {
            mkdir($fulldir, 0755, true);
        }
        if (!$f->copy_content_to($dest)) {
            throw new moodle_exception('archivefailed', 'local_flatarchive');
        }
    }
    // Keep each chapter file area separate to avoid filename collisions.
    // We do not use file_rewrite_pluginfile_urls() as it expects 6+ params and produces absolute URLs.
    $content = str_replace('@@PLUGINFILE@@/', $relativeprefix . $relativepath . '/', $content);
    return $content;
}


/**
 * Write generated HTML and fail before advertising a partial archive.
 * @param string $path Destination.
 * @param string $content File contents.
 */
function local_flatarchive_write_file(string $path, string $content): void {
    if (file_put_contents($path, $content) !== strlen($content)) {
        throw new moodle_exception('archivefailed', 'local_flatarchive');
    }
}

/**
 * Group exported links by section, preserving course order and distinct section IDs.
 *
 * @param stdClass $course Course record.
 * @param course_modinfo $modinfo Course structure.
 * @param array $links Trusted rendered list items indexed by course module ID.
 * @return array Non-empty sections with their selected resource links.
 */
function local_flatarchive_group_index_links(stdClass $course, course_modinfo $modinfo, array $links): array {
    $result = [];
    $sectionmodules = $modinfo->get_sections();
    foreach ($modinfo->get_section_info_all() as $section) {
        $selected = [];
        foreach ($sectionmodules[$section->section] ?? [] as $cmid) {
            if (isset($links[$cmid])) {
                // The section status belongs to its heading in the course index.
                $sectionbadge = '<span class="fa-vis-badge fa-vis-section">'
                    . get_string('visibility_section_hidden', 'local_flatarchive') . '</span>';
                $selected[] = str_replace($sectionbadge, '', $links[$cmid]);
            }
        }
        if ($selected) {
            $result[] = [
                'id' => $section->id,
                'name' => get_section_name($course, $section),
                'visible' => (bool)$section->visible,
                'links' => $selected,
            ];
        }
    }
    return $result;
}
