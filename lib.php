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
// Library for local_flatarchive.

/**
 * Course navigation integration.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
