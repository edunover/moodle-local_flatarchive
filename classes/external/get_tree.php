<?php
// This file is part of Moodle - http://moodle.org/
//
// External function to return course sections and Page/Book modules (visible and hidden).

namespace local_flatarchive\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

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
     * Retourne l’arborescence des sections et des modules Page et Livre du cours,
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

                // The HTML exporter supports Pages and Books.
                if (!in_array($cm->modname, ['page', 'book'], true)) {
                    continue;
                }

                $sectiondata['modules'][] = [
                    'cmid' => $cm->id,
                    'name' => $cm->name,
                    'type' => $cm->modname,
                    'visible' => (bool) $cm->visible,
                    'available' => (bool) $cm->available,
                ];
            }

            // On ajoute la section uniquement si elle contient au moins une Page ou un Livre.
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
                            'visible' => new external_value(PARAM_BOOL, 'Module visible'),
                            'available' => new external_value(PARAM_BOOL, 'Module disponible'),
                        ])
                    ),
                ])
            ),
        ]);
    }
}
