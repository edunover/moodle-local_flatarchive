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

namespace local_flatarchive\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use context_course;

/**
 * Return supported course resources, including hidden resources for authorised users.
 *
 * @package local_flatarchive
 * @copyright 2025 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_tree extends external_api {
    /**
     * Définition des paramètres attendus.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'ID du cours'),
        ]);
    }

    /**
     * Retourne l’arborescence des sections et des ressources prises en charge du cours,
     * incluant les éléments visibles et cachés.
     *
     * @param int $courseid
     * @return array
     */
    public static function execute(int $courseid): array {

        // Validation des paramètres.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        // Chargement du cours.
        $course = get_course($params['courseid']);
        require_login($course);

        // Contexte et sécurité.
        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_capability('local/flatarchive:archive', $context);

        // Récupération optimisée des infos du cours.
        $modinfo = get_fast_modinfo($course);

        $sections = [];
        $sectionmodules = $modinfo->get_sections();

        // Parcours des sections dans l’ordre réel du cours.
        foreach ($modinfo->get_section_info_all() as $sectioninfo) {
            $sectiondata = [
                'id' => $sectioninfo->id,
                'name' => get_section_name($course, $sectioninfo),
                'visible' => (bool) $sectioninfo->visible,
                'modules' => [],
            ];

            // CMIDs de la section.
            $cmids = $sectionmodules[$sectioninfo->section] ?? [];

            foreach ($cmids as $cmid) {
                $cm = $modinfo->get_cm($cmid);

                // The HTML exporter supports Pages, Books, text and media areas, Files and Folders.
                $supported = ['page', 'book', 'label', 'resource', 'folder', 'glossary',
                    'wiki', 'feedback', 'choice', 'forum', 'lesson'];
                if (!in_array($cm->modname, $supported, true)) {
                    continue;
                }

                // Complete lesson exports expose solutions and require authoring permission.
                if ($cm->modname === 'lesson' && !has_capability('mod/lesson:edit', \context_module::instance($cm->id))) {
                    continue;
                }

                $sectiondata['modules'][] = [
                    'cmid' => $cm->id,
                    'name' => $cm->name,
                    'type' => $cm->modname,
                    'iconurl' => $cm->get_icon_url()->out(false),
                    'modfullname' => (string)$cm->get_module_type_name(),
                    'visible' => (bool) $cm->visible,
                    'available' => (bool) $cm->available,
                ];
            }

            // Include only sections containing supported resources.
            if (!empty($sectiondata['modules'])) {
                $sections[] = $sectiondata;
            }
        }

        return [
            'sections' => $sections,
        ];
    }

    /**
     * Définition de la structure retournée.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'ID de la section'),
                    'name' => new external_value(PARAM_TEXT, 'Nom de la section'),
                    'visible' => new external_value(PARAM_BOOL, 'Section visible'),
                    'modules' => new external_multiple_structure(
                        new external_single_structure([
                            'cmid' => new external_value(PARAM_INT, 'ID du course module'),
                            'name' => new external_value(PARAM_TEXT, 'Nom du module'),
                            'type' => new external_value(PARAM_ALPHA, 'Type de module'),
                            'iconurl' => new external_value(PARAM_URL, 'Activity icon URL'),
                            'modfullname' => new external_value(PARAM_TEXT, 'Localised activity type'),
                            'visible' => new external_value(PARAM_BOOL, 'Module visible'),
                            'available' => new external_value(PARAM_BOOL, 'Module disponible'),
                        ])
                    ),
                ])
            ),
        ]);
    }
}
