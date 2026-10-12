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
$string['processing_archive'] = 'Traitement d\'archivage';
$string['about_course'] = 'À propos de ce cours';
$string['coursenotfound'] = 'Cours introuvable.';
$string['footer_copyright'] = 'Flatarchive {$a} &#169; EDUNOVER';
$string['visibility_hidden'] = 'caché';
$string['visibility_stealth'] = 'caché mais disponible';
$string['visibility_section_hidden'] = 'section cachée';
$string['visibility_chapter_hidden'] = 'chapitre caché';
$string['noresources'] = 'Aucune activité ou ressource sélectionnable pour cet export.';
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

$string['forumscope'] = 'Seuls les messages visibles sont inclus. Les réponses sont regroupées sous leur message parent.';
$string['backtoforum'] = 'Retour au forum';
$string['forumunknownauthor'] = 'Auteur inconnu';
$string['forummodified'] = 'Modifié : {$a}';
$string['forumprivate'] = 'Réponse privée';
$string['forumparent'] = 'Voir le message parent';
$string['forumattachments'] = 'Pièces jointes';
$string['forumempty'] = 'Aucun message accessible.';

$string['forumpinned'] = 'Épinglée';

$string['archivetags'] = 'Tags';
$string['archiveactivitytags'] = 'Tags de l’activité';
$string['archivecoursetags'] = 'Tags du cours';

// Static Lesson export.
$string['lessonscope'] = 'Contenu pédagogique et corrigés statiques. Les rapports de tentatives accessibles figurent ci-dessous. Les sauts dynamiques sont décrits sans être exécutés.';
$string['backtolesson'] = 'Retour à la leçon';
$string['lessonunknown'] = 'Type de page inconnu ({$a})';
$string['lessonmultiple'] = 'Plusieurs réponses peuvent être sélectionnées.';
$string['lessonessay'] = 'Question de composition : évaluation manuelle dans Moodle. Consulte les rapports accessibles pour les travaux soumis.';
$string['lessonpair'] = 'Paire à apparier';
$string['lessonanswer'] = 'Réponse / navigation';
$string['lessoncorrect'] = 'Appariement correct';
$string['lessonincorrect'] = 'Appariement incorrect';
$string['lessonmatch'] = 'Valeur correspondante';
$string['lessonfeedback'] = 'Feedback';
$string['lessonscore'] = 'Score configuré : {$a}';
$string['lessonempty'] = 'Cette leçon ne contient aucune page.';
$string['lessonend'] = 'Fin de la leçon';
$string['lessondynamic'] = 'Destination dynamique dépendant de la tentative Moodle. Utilise le sommaire pour consulter les pages.';
$string['lessongoto'] = 'Aller à : {$a}';
$string['lessonmissing'] = 'Destination indisponible dans cette leçon.';

// Lesson attempt reports.
$string['lessonattempts'] = 'Tentatives des étudiants';
$string['lessonreportsdenied'] = 'Rapports de tentatives non inclus : droit de consultation des rapports requis.';
$string['lessonattemptnumber'] = 'Tentative {$a}';
$string['lessoncompleted'] = 'Terminée';
$string['lessonincomplete'] = 'Non terminée';
$string['lessonstarted'] = 'Début';
$string['lessonlastactivity'] = 'Dernière activité enregistrée';
$string['lessonrecordedgrade'] = 'Note enregistrée dans la leçon : {$a} %';
$string['lessonanswered'] = 'Réponse enregistrée';
$string['lessonvisited'] = 'Visites enregistrées des pages de contenu';
$string['lessonreportsscope'] = 'Les rapports couvrent les inscrits actifs visibles dans le contexte de groupe courant. Chaque tentative est distincte ; les notes enregistrées ne sont pas recalculées.';
$string['lessonnoattempts'] = 'Aucune tentative enregistrée visible.';
$string['lessonunreadable'] = 'La réponse enregistrée ne peut pas être décodée.';
$string['lessonungraded'] = 'Pas encore évaluée';
$string['lessonearnedscore'] = 'Score attribué : {$a}';
$string['lessonanswercorrect'] = 'Enregistrée comme correcte';
$string['lessonanswerincorrect'] = 'Enregistrée comme incorrecte';

$string['backtocourseindex'] = 'Retour à l’index du cours';

$string['archivegenerated'] = 'Archive générée';
$string['quizarchivescope'] = 'Questionnaire statique et tentatives enregistrées accessibles. Aucune réponse ne peut être envoyée ; les notes ne sont pas recalculées.';
$string['quizquestionnaire'] = 'Questionnaire et solutions';
$string['quizattempts'] = 'Tentatives enregistrées';
$string['quizstoredmarks'] = 'Points enregistrés : {$a}';
$string['quiznoattempts'] = 'Aucune tentative consultable avec tes droits actuels.';
$string['quizslot'] = 'Emplacement de question {$a}';
$string['quizrandomslot'] = 'Emplacement de question aléatoire. Les questions réellement tirées sont conservées dans les tentatives accessibles. Aucun nouveau tirage n’est effectué.';
$string['quizquestionunavailable'] = 'Question indisponible ou droit d’utilisation de cette question absent.';
$string['quizunsupported'] = 'Type de question non encore pris en charge pour un rendu hors ligne complet : {$a}. Les résumés disponibles de la tentative suivent.';
$string['backtoquiz'] = 'Retour au test';

$string['unsupportedactivity'] = 'Non pris en charge';
$string['exportnotpermitted'] = 'Droits insuffisants pour l’export';

// Database archive.
$string['datanotapproved'] = 'Non approuvée';
$string['backtodata'] = 'Retour à la base de données';
$string['dataviewentry'] = 'Voir la fiche';
$string['dataempty'] = 'Aucune fiche disponible pour cet export.';
$string['datarequiredentries'] = 'Réglage du cours : {$a} contributions requises avant de consulter les autres fiches. Cette condition ne limite pas l’archive.';

// Archived activity settings.
$string['archivesettings'] = 'Paramètres';
$string['settingssnapshot'] = 'Configuration relevée le :';
$string['settingsscope'] = 'Relevé documentaire. La présence de JavaScript personnalisé est indiquée sans copier son code. La configuration interne et les paramètres de champs tiers ne sont pas inclus.';
$string['settingsvisibility'] = 'Visibilité';
$string['settingsvisible'] = 'Visible sur la page du cours';
$string['settingshidden'] = 'Cachée';
$string['settingsstealth'] = 'Cachée mais disponible';
$string['settingsshowdescription'] = 'Afficher la description sur la page du cours';
$string['settingsgroupforced'] = 'Mode de groupes imposé par le cours';
$string['settingsaccess'] = 'Restrictions d’accès';
$string['settingscompletion'] = 'Suivi d’achèvement';
$string['settingscompletion0'] = 'Désactivé';
$string['settingscompletion1'] = 'Manuel';
$string['settingscompletion2'] = 'Automatique';
$string['settingscompletionview'] = 'Consultation requise';
$string['settingscompletiongrade'] = 'Note requise';
$string['settingscompletionexpected'] = 'Date d’achèvement prévue';
$string['settingsrequiredentries'] = 'Fiches requises (ancien réglage)';
$string['settingsrequiredentriestoview'] = 'Contributions requises avant consultation';
$string['settingsmaxentries'] = 'Nombre maximal de fiches par participant';
$string['settingscompletionentries'] = 'Fiches requises pour l’achèvement';
$string['settingsrssarticles'] = 'Fiches dans le flux RSS';
$string['settingstimecreated'] = 'Date de création';
$string['settingstimemodified'] = 'Date de modification';
$string['settingsapprovalstatus'] = 'État d’approbation';
$string['settingssort'] = 'Champ de tri par défaut';
$string['settingssortdirection'] = 'Sens du tri';
$string['settingsascending'] = 'Croissant';
$string['settingsdescending'] = 'Décroissant';
$string['settingsrating'] = 'Agrégation des évaluations';
$string['settingsrating0'] = 'Aucune évaluation';
$string['settingsrating1'] = 'Moyenne';
$string['settingsrating2'] = 'Nombre';
$string['settingsrating3'] = 'Maximum';
$string['settingsrating4'] = 'Minimum';
$string['settingsrating5'] = 'Somme';
$string['settingsscale'] = 'Barème ou note maximale';
$string['settingsratingfrom'] = 'Évaluations à partir du';
$string['settingsratingto'] = 'Évaluations jusqu’au';
$string['settingsgradepass'] = 'Note pour réussir';
$string['settingsgradecategory'] = 'Catégorie de notes';
$string['settingsfields'] = 'Définition des champs';
$string['settingsfieldtype'] = 'Type de champ';
$string['settingsfieldrequired'] = 'Champ obligatoire';
$string['settingsfieldoptions'] = 'Choix proposés';
$string['settingsautolink'] = 'Liens automatiques';
$string['settingscolumns'] = 'Largeur de l’éditeur (colonnes)';
$string['settingsrows'] = 'Hauteur de l’éditeur (lignes)';
$string['settingsmaxbytes'] = 'Taille maximale du fichier (octets ; 0 = héritée)';
$string['settingswidth'] = 'Largeur de l’image (pixels)';
$string['settingsheight'] = 'Hauteur de l’image (pixels)';
$string['settingsthumbwidth'] = 'Largeur de la miniature (pixels)';
$string['settingsthumbheight'] = 'Hauteur de la miniature (pixels)';
$string['settingslinktext'] = 'Libellé du lien';
$string['settingsnewwindow'] = 'Ouvrir dans une nouvelle fenêtre';
$string['settingstemplates'] = 'Modèles de présentation (source)';
$string['settingslisttemplateheader'] = 'En-tête de la liste';
$string['settingslisttemplate'] = 'Élément de la liste';
$string['settingslisttemplatefooter'] = 'Pied de la liste';
$string['settingssingletemplate'] = 'Fiche unique';
$string['settingsaddtemplate'] = 'Formulaire d’ajout';
$string['settingsasearchtemplate'] = 'Recherche avancée';
$string['settingsrsstemplate'] = 'Fiche RSS';
$string['settingsrsstitletemplate'] = 'Titre RSS';
$string['settingscsstemplate'] = 'CSS personnalisé';
$string['settingsjavascript'] = 'JavaScript personnalisé présent';

// Course and activity settings snapshots.
$string['settingsgeneralscope'] = 'Instantané des paramètres standard du cours ou de l’activité. Les mots de passe, secrets, dérogations individuelles et configurations de plugins tiers ne sont pas inclus.';
$string['settingsbackactivity'] = 'Retour à l’activité';
$string['settingspasswordset'] = 'Mot de passe configuré (valeur omise)';
$string['settingscourselang'] = 'Langue imposée';
$string['settingscoursenews'] = 'Nombre d’annonces';
$string['settingscoursegrades'] = 'Afficher le carnet de notes aux étudiants';
$string['settingscoursereports'] = 'Afficher les rapports d’activité';
$string['settingscoursedates'] = 'Afficher les dates des activités';
$string['settingscoursecompletion'] = 'Afficher les conditions d’achèvement';
$string['settingsreviewduring'] = 'Pendant la tentative';
$string['settingsreviewimmediately'] = 'Immédiatement après la tentative';
$string['settingsreviewopen'] = 'Plus tard, tant que le test est ouvert';
$string['settingsreviewclosed'] = 'Après la fermeture du test';
$string['settingsreviewattempt'] = 'La tentative';
$string['settingsreviewcorrectness'] = 'Correction des réponses';
$string['settingsreviewmarks'] = 'Notes';
$string['settingsreviewspecificfeedback'] = 'Feedback spécifique';
$string['settingsreviewgeneralfeedback'] = 'Feedback général';
$string['settingsreviewrightanswer'] = 'Réponse correcte';
$string['settingsreviewoverallfeedback'] = 'Feedback global';
$string['settingfield_display'] = 'Mode d’affichage';
$string['settingfield_multiple_submit'] = 'Autoriser plusieurs remises';
$string['settingfield_publish_stats'] = 'Publier l’analyse';
$string['settingfield_timeopen'] = 'Date d’ouverture';
$string['settingfield_timeclose'] = 'Date de fermeture';
$string['settingfield_showresults'] = 'Quand afficher les résultats';
$string['settingfield_maxbytes'] = 'Taille maximale des pièces jointes';
$string['settingfield_grade'] = 'Note maximale';
$string['settingfield_usemaxgrade'] = 'Utiliser la meilleure note des tentatives';
$string['settingfield_maxattempts'] = 'Nombre maximal d’essais par question';
$string['settingfield_feedback'] = 'Afficher le feedback par défaut';
$string['settingfield_minquestions'] = 'Nombre minimal de questions';
$string['settingfield_maxpages'] = 'Nombre maximal de pages';
$string['settingfield_displayleft'] = 'Afficher le menu';
$string['settingfield_attemptonlast'] = 'Chaque tentative complète la précédente';
$string['settingfield_questiondecimalpoints'] = 'Décimales des notes des questions';
$string['settingfield_allowofflineattempts'] = 'Autoriser les tentatives hors ligne';
$string['settingsdisplayauto'] = 'Automatique';
$string['settingsdisplayembed'] = 'Intégré';
$string['settingsdisplayframe'] = 'Cadre';
$string['settingsdisplaynew'] = 'Nouvelle fenêtre';
$string['settingsdisplaydownload'] = 'Forcer le téléchargement';
$string['settingsdisplayopen'] = 'Ouvrir';
$string['settingsdisplaypopup'] = 'Fenêtre surgissante';
$string['settingsprintintro'] = 'Afficher la description';
$string['settingsprintheading'] = 'Afficher le titre';
$string['settingsshowsize'] = 'Afficher la taille du fichier';
$string['settingsshowtype'] = 'Afficher le type du fichier';
$string['settingsshowdate'] = 'Afficher la date de modification du fichier';
$string['settingspopupwidth'] = 'Largeur de la fenêtre (pixels)';
$string['settingspopupheight'] = 'Hauteur de la fenêtre (pixels)';
$string['settingfield_completiontimespent'] = 'Temps minimal requis pour l’achèvement';
$string['settingsnavtoc'] = 'Table des matières seule (ancienne navigation)';
$string['settingsnavimages'] = 'Images (ancienne navigation)';
$string['settingsnavtext'] = 'Texte (ancienne navigation)';
$string['settingsemptyfield'] = 'Champ vide';

$string['urlexternalnotice'] = 'Destination externe : une connexion Internet peut être nécessaire. Le site de destination n’est pas inclus dans cette archive.';
$string['urlparametersnotice'] = 'Les paramètres dynamiques Moodle ne sont pas ajoutés à ce lien archivé.';
$string['assignarchivescope'] = 'Copie statique : texte en ligne, fichiers remis, notes du devoir, commentaires et fichiers de feedback. Les grilles d’évaluation avancée et annotations PDF ne sont pas reproduites.';
$string['assignanonymous'] = 'Participant {$a}';
$string['assignrecords'] = 'Remises et évaluations enregistrées';
$string['assignnorecords'] = 'Aucune remise ou évaluation disponible pour cet export avec tes droits.';
$string['assignattempt'] = 'Tentative {$a}';
$string['assigngroupsubmission'] = 'Remise de groupe';
$string['assignnotgraded'] = 'Non évalué';
$string['assignunsupported'] = 'Contenu non exporté pour cette extension : {$a}';
$string['assigngrade'] = 'Note du devoir';
