<?php
// Library for local_flatarchive.

defined('MOODLE_INTERNAL') || die();

/**
 * Ajoute l’entrée Flat archive dans la navigation du cours (menu plus).
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function local_flatarchive_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context): void {
    if (!has_capability('local/flatarchive:archive', $context)) {
        return;
    }

    $url = new moodle_url('/local/flatarchive/index.php', ['id' => $course->id]);

    // Identifiant stable pour éviter les doublons.
    $key = 'local_flatarchive';

    // On l’ajoute dans la navigation du cours. Moodle va l’afficher dans le menu plus selon le thème.
    $node = $navigation->add(
        get_string('flatarchive', 'local_flatarchive'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        $key,
        new pix_icon('i/export', '')
    );

    // Optionnel : forcer une place logique.
    $node->showinflatnavigation = true;
}
