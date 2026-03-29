<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Prépare un HTML Moodle pour un archivage pérenne Flatarchive.
 *
 * Règles :
 * - Emojis Unicode → [ICON:U+XXXX]
 * - Icônes HTML → [ICON]
 * - TOUTES les images sont copiées localement
 * - Aucune dépendance pluginfile / Moodle ensuite
 *
 * @param string  $html
 * @param context $context UNUSED ici mais conservé pour évolution future
 * @param string  $assetsdir
 * @return string
 */
function flatarchive_prepare_html_for_archive(
    string $html,
    context $context,
    string $assetsdir
): string {

    global $CFG;

    // ------------------------------------------------------------------
    // 0) Dossier assets
    // ------------------------------------------------------------------
    if (!is_dir($assetsdir)) {
        mkdir($assetsdir, $CFG->directorypermissions, true);
    }

    // ------------------------------------------------------------------
    // 1) Emojis Unicode → [ICON:U+XXXX]
    // ------------------------------------------------------------------
    $html = preg_replace_callback(
        '/[\p{So}\p{Sk}]/u',
        function ($m) {
            $cp = mb_ord($m[0], 'UTF-8');
            return '[ICON:U+' . strtoupper(dechex($cp)) . ']';
        },
        $html
    );

    // ------------------------------------------------------------------
    // 2) DOMDocument (sans <html><body>)
    // ------------------------------------------------------------------
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML(
        '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    libxml_clear_errors();

    // ------------------------------------------------------------------
    // 3) Traitement des images
    // ------------------------------------------------------------------
    $counter = 1;

    foreach ($dom->getElementsByTagName('img') as $img) {

        $src = trim($img->getAttribute('src'));
        if ($src === '') {
            continue;
        }

        $localname = 'image_' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
        $extension = pathinfo(parse_url($src, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
        $extension = $extension ?: 'bin';

        $localpath = $assetsdir . '/' . $localname . '.' . $extension;
        $copied = false;

        // Images HTTP / HTTPS (y compris pluginfile déjà résolu)
        if (preg_match('#^https?://#', $src)) {
            try {
                $content = download_file_content($src);
                if ($content !== false) {
                    file_put_contents($localpath, $content);
                    $img->setAttribute('src', basename($localpath));
                    $copied = true;
                }
            } catch (Throwable $e) {
                // silence volontaire
            }
        }

        if (!$copied) {
            $text = $dom->createTextNode('[IMAGE NON ARCHIVABLE]');
            $img->parentNode->replaceChild($text, $img);
        }
    }

    // ------------------------------------------------------------------
    // 4) Icônes HTML restantes → [ICON]
    // ------------------------------------------------------------------
    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//span[contains(@class,"icon")] | //i[contains(@class,"fa")]') as $node) {
        $text = $dom->createTextNode('[ICON]');
        $node->parentNode->replaceChild($text, $node);
    }

    // ------------------------------------------------------------------
    // 5) HTML final
    // ------------------------------------------------------------------
    return $dom->saveHTML();
}

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
    } elseif (!$cm->visibleoncoursepage) {
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
