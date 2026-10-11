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
 * French language strings.
 *
 * @package local_flatarchive
 * @copyright 2025 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Flat archive';
$string['flatarchive'] = 'Flat archive';
$string['backtoflatarchive'] = 'Retour à Flat Archive';
$string['archivecourse'] = 'Archiver le contenu du cours';
$string['selectall'] = 'Tout sélectionner';
$string['deselectall'] = 'Tout désélectionner';
$string['archiveselection'] = 'Archiver la sélection';
$string['loading'] = 'Chargement';
$string['backendnotready'] = 'Backend non connecté. Prochaine étape : implémenter get_tree et start_job.';
$string['nothingselected'] = 'Sélectionnez au moins un élément.';
$string['status_idle'] = 'Prêt';
$string['status_running'] = 'En cours';
$string['status_done'] = 'Terminé';
$string['status_failed'] = 'Échec';
$string['downloadzip'] = 'Télécharger le ZIP';
$string['hidden'] = 'caché';
$string['archive_description'] = 'Cet outil vous permet d\'archiver le contenu de votre cours sous forme de pages HTML statiques.';
$string['generate_html_archive'] = 'Générer l\'archive HTML';
$string['archive_success'] = 'Votre archive a été générée.';
$string['noselection'] = 'Aucun contenu sélectionné.';
$string['archive_success_download'] = 'Télécharger l\'archive ZIP';
$string['archive_downloaded_deleted'] = 'Archive téléchargée et supprimée.';
$string['processing_archive'] = 'Traitement d\'archivage';
$string['about_course'] = 'À propos de ce cours';
$string['coursenotfound'] = 'Cours introuvable.';
$string['footer_copyright'] = 'Flatarchive {$a} &#169; EDUNOVER';
$string['visibility_hidden'] = 'caché';
$string['visibility_stealth'] = 'caché de la page de cours';
$string['visibility_section_hidden'] = 'section cachée';
$string['visibility_chapter_hidden'] = 'chapitre caché';
$string['noresources'] = 'Aucune ressource prise en charge à archiver dans ce cours.';
$string['archivefailed'] = 'Impossible de créer l’archive. Vérifie l’espace disque et les droits d’écriture.';
$string['invalidselection'] = 'La sélection contient une ressource non prise en charge ou extérieure à ce cours.';

$string['flatarchive:archive'] = 'Archiver les ressources du cours';

$string['resourcefilenotfound'] = 'La ressource Fichier « {$a} » ne contient aucun fichier à archiver.';

$string['archiveunavailable'] = 'Archive indisponible';

$string['archiveunavailable_help'] = 'Cette archive temporaire n’est plus disponible. Retourne dans ton cours et génère une nouvelle archive.';

$string['downloadavailability'] = 'Tu peux télécharger cette archive plusieurs fois pendant une heure après sa génération, dans cette session.';

$string['foldercontents'] = 'Fichiers et sous-dossiers';
$string['folderempty'] = 'Ce dossier est vide.';

$string['glossaryscope'] = 'Seules les entrées approuvées sont incluses. Les commentaires accessibles et les noms des auteurs sont archivés ; évaluations et profils exclus.';
$string['glossaryexcluded'] = 'Entrées en attente exclues : {$a}.';
$string['glossaryaliases'] = 'Synonymes';
$string['glossarycategories'] = 'Catégories';
$string['glossaryattachments'] = 'Pièces jointes';
$string['glossaryempty'] = 'Aucune entrée approuvée.';
$string['glossaryentries'] = 'Entrées du glossaire';
$string['backtoglossary'] = 'Retour au glossaire';
$string['glossaryauthor'] = 'Auteur';
$string['glossarycomments'] = 'Commentaires';
$string['glossaryunknownauthor'] = 'Auteur inconnu ou supprimé';

$string['wikiscope'] = 'Dernières révisions des pages accessibles, fichiers et commentaires. Historique exclu ; ressources externes non copiées.';
$string['wikispace'] = 'Espace wiki {$a}';
$string['wikiunknowngroup'] = 'Groupe inconnu';
$string['wikiunknownauthor'] = 'Auteur inconnu ou supprimé';
$string['wikifirstpage'] = 'Page de départ';
$string['wikiempty'] = 'Aucune page accessible.';
$string['wikiattachments'] = 'Fichiers de cet espace wiki';
$string['backtowiki'] = 'Retour au wiki';
$string['wikilastauthor'] = 'Dernière révision par';
$string['wikicomments'] = 'Commentaires';
$string['wikinotarchived'] = 'Page non archivée';

$string['archive_success_size'] = 'Votre archive a été générée. ({$a})';

$string['feedbackscope'] = 'Questionnaire statique et réponses soumises accessibles. Les brouillons sont exclus. Les réponses anonymes ne comportent ni auteur ni date de soumission. Les réponses libres sont conservées telles que saisies.';

$string['feedbackquestions'] = 'Questionnaire';

$string['feedbackcondition'] = 'Affiché lorsque « {$a->question} » vaut « {$a->value} ».';

$string['feedbackempty'] = 'Ce questionnaire est vide.';

$string['feedbackaftersubmit'] = 'Message après soumission';

$string['feedbackresponses'] = 'Réponses individuelles';

$string['feedbackresponsesrestricted'] = 'Les réponses individuelles sont exclues : le droit de consulter les rapports est nécessaire.';

$string['feedbackresponse'] = 'Réponse {$a}';

$string['backtofeedback'] = 'Retour au Feedback';

$string['feedbacknoresponses'] = 'Aucune réponse soumise accessible dans ce cours et tes groupes.';

$string['feedbackunsupported'] = 'Ce type de question n’est pas pris en charge par l’archive.';

$string['choicemultiple'] = 'Plusieurs options peuvent être sélectionnées.';
$string['choicesingle'] = 'Une seule option peut être sélectionnée.';
$string['choiceoptions'] = 'Options';
$string['choicelimit'] = 'Nombre maximal de réponses : {$a}';
$string['choiceresults'] = 'Résultats';
$string['choicerestricted'] = 'Les résultats ne sont pas disponibles à l’export avec tes droits actuels ou les'
    . ' paramètres de publication.';
$string['choicescope'] = 'Instantané des réponses soumises pour le groupe courant et les inscriptions retenues'
    . ' par Moodle. Les non-répondants et les dates de réponse ne sont pas inclus.';
$string['choicecount'] = 'Réponses : {$a}';

$string['forumscope'] = 'Seuls les discussions et messages visibles par la personne qui exporte sont inclus.'
    . ' Les messages sont chronologiques ; les liens de réponse conservent leur filiation.';
$string['backtoforum'] = 'Retour au forum';
$string['forumunknownauthor'] = 'Auteur inconnu';
$string['forummodified'] = 'Modifié : {$a}';
$string['forumprivate'] = 'Réponse privée';
$string['forumparent'] = 'Voir le message parent';
$string['forumattachments'] = 'Pièces jointes';
$string['forumempty'] = 'Aucun message accessible.';
