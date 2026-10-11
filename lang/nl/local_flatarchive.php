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
 * Dutch language strings.
 *
 * @package local_flatarchive
 * @copyright 2025 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Flat archive';
$string['flatarchive'] = 'Flat archive';
$string['backtoflatarchive'] = 'Terug naar Flat Archive';
$string['archivecourse'] = 'Cursus archiveren';
$string['selectall'] = 'Alles selecteren';
$string['deselectall'] = 'Alles deselecteren';
$string['archiveselection'] = 'Selectie archiveren';
$string['loading'] = 'Laden';
$string['backendnotready'] = 'Backend nog niet gekoppeld. Volgende stap: get_tree en start_job implementeren.';
$string['nothingselected'] = 'Selecteer minstens één bron.';
$string['status_idle'] = 'Klaar';
$string['status_running'] = 'Bezig';
$string['status_done'] = 'Klaar';
$string['status_failed'] = 'Mislukt';
$string['downloadzip'] = 'ZIP downloaden';
$string['hidden'] = 'verborgen';
$string['visibility_hidden'] = 'verborgen';
$string['visibility_stealth'] = 'verborgen van cursuspagina';
$string['visibility_section_hidden'] = 'verborgen sectie';
$string['footer_copyright'] = 'Flatarchive {$a} &#169; EDUNOVER';
$string['visibility_chapter_hidden'] = 'verborgen hoofdstuk';
$string['noresources'] = 'Er zijn geen ondersteunde bronnen om te archiveren.';
$string['archivefailed'] = 'Het archief kon niet worden gemaakt. Controleer de vrije schijfruimte en schrijfrechten.';
$string['invalidselection'] = 'De selectie bevat een niet-ondersteunde bron of een bron uit een andere cursus.';

$string['flatarchive:archive'] = 'Cursusbronnen archiveren';

$string['resourcefilenotfound'] = 'De bestandsbron "{$a}" bevat geen bestand om te archiveren.';

$string['archiveunavailable'] = 'Archief niet meer beschikbaar';

$string['archiveunavailable_help'] = 'Dit tijdelijke archief is niet meer beschikbaar. Ga terug naar je cursus en maak een nieuw archief.';

$string['downloadavailability'] = 'Je kunt dit archief gedurende één uur na het aanmaken opnieuw downloaden binnen deze sessie.';

$string['foldercontents'] = 'Bestanden en submappen';
$string['folderempty'] = 'Deze map is leeg.';

$string['glossaryscope'] = 'Alleen goedgekeurde items worden opgenomen. Leesbare reacties en auteursnamen worden bewaard; beoordelingen en profielen niet.';
$string['glossaryexcluded'] = 'Uitgesloten items in afwachting: {$a}.';
$string['glossaryaliases'] = 'Synoniemen';
$string['glossarycategories'] = 'Categorieën';
$string['glossaryattachments'] = 'Bijlagen';
$string['glossaryempty'] = 'Geen goedgekeurde items.';
$string['glossaryentries'] = 'Woordenlijstitems';
$string['backtoglossary'] = 'Terug naar de woordenlijst';
$string['glossaryauthor'] = 'Auteur';
$string['glossarycomments'] = 'Reacties';
$string['glossaryunknownauthor'] = 'Onbekende of verwijderde auteur';

$string['wikiscope'] = 'Laatste versies van leesbare pagina’s, bestanden en reacties. Geen versiegeschiedenis; externe bronnen blijven extern.';
$string['wikispace'] = 'Wikiruimte {$a}';
$string['wikiunknowngroup'] = 'Onbekende groep';
$string['wikiunknownauthor'] = 'Onbekende of verwijderde auteur';
$string['wikifirstpage'] = 'Startpagina';
$string['wikiempty'] = 'Geen leesbare pagina’s.';
$string['wikiattachments'] = 'Bestanden in deze wikiruimte';
$string['backtowiki'] = 'Terug naar wiki';
$string['wikilastauthor'] = 'Laatste versie door';
$string['wikicomments'] = 'Reacties';
$string['wikinotarchived'] = 'Pagina niet gearchiveerd';

$string['archive_success_size'] = 'Je archief is aangemaakt. ({$a})';

$string['feedbackscope'] = 'Statische vragenlijst en toegankelijke ingediende antwoorden. Concepten worden uitgesloten. Anonieme antwoorden hebben geen auteur of indieningsdatum. Vrije tekst blijft ongewijzigd.';

$string['feedbackquestions'] = 'Vragenlijst';

$string['feedbackcondition'] = 'Getoond wanneer “{$a->question}” gelijk is aan “{$a->value}”.';

$string['feedbackempty'] = 'Deze vragenlijst is leeg.';

$string['feedbackaftersubmit'] = 'Bericht na indiening';

$string['feedbackresponses'] = 'Individuele antwoorden';

$string['feedbackresponsesrestricted'] = 'Individuele antwoorden zijn niet opgenomen: toestemming om rapporten te bekijken is vereist.';

$string['feedbackresponse'] = 'Antwoord {$a}';

$string['backtofeedback'] = 'Terug naar Feedback';

$string['feedbacknoresponses'] = 'Er zijn geen ingediende antwoorden beschikbaar in deze cursus en je groepen.';

$string['feedbackunsupported'] = 'Dit vraagtype wordt niet ondersteund door het archief.';

$string['choicemultiple'] = 'Meerdere opties kunnen worden geselecteerd.';
$string['choicesingle'] = 'Er kan slechts één optie worden geselecteerd.';
$string['choiceoptions'] = 'Opties';
$string['choicelimit'] = 'Maximumaantal antwoorden: {$a}';
$string['choiceresults'] = 'Resultaten';
$string['choicerestricted'] = 'De resultaten kunnen niet worden geëxporteerd met je huidige rechten of publicatie-instellingen.';
$string['choicescope'] = 'Momentopname van ingediende antwoorden voor de huidige Moodle-groep en inschrijvingen.'
    . ' Niet-respondenten en antwoorddatums zijn niet opgenomen.';
$string['choicecount'] = 'Antwoorden: {$a}';

$string['forumscope'] = 'Alleen discussies en berichten die de exporteur mag bekijken worden opgenomen.'
    . ' Berichten staan chronologisch; antwoordlinks behouden hun verband.';
$string['backtoforum'] = 'Terug naar het forum';
$string['forumunknownauthor'] = 'Onbekende auteur';
$string['forummodified'] = 'Gewijzigd: {$a}';
$string['forumprivate'] = 'Privéantwoord';
$string['forumparent'] = 'Bovenliggend bericht bekijken';
$string['forumattachments'] = 'Bijlagen';
$string['forumempty'] = 'Geen toegankelijke berichten.';
