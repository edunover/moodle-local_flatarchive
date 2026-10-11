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
$string['visibility_stealth'] = 'verborgen maar beschikbaar';
$string['visibility_section_hidden'] = 'verborgen sectie';
$string['footer_copyright'] = 'Flatarchive {$a} &#169; EDUNOVER';
$string['visibility_chapter_hidden'] = 'verborgen hoofdstuk';
$string['noresources'] = 'Er zijn geen activiteiten of bronnen selecteerbaar voor deze export.';
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

$string['forumscope'] = 'Alleen zichtbare berichten worden opgenomen. Antwoorden staan onder hun bovenliggende bericht.';
$string['backtoforum'] = 'Terug naar het forum';
$string['forumunknownauthor'] = 'Onbekende auteur';
$string['forummodified'] = 'Gewijzigd: {$a}';
$string['forumprivate'] = 'Privéantwoord';
$string['forumparent'] = 'Bovenliggend bericht bekijken';
$string['forumattachments'] = 'Bijlagen';
$string['forumempty'] = 'Geen toegankelijke berichten.';

$string['forumpinned'] = 'Vastgezet';

$string['archivetags'] = 'Tags';
$string['archiveactivitytags'] = 'Activiteitstags';
$string['archivecoursetags'] = 'Cursustags';

// Static Lesson export.
$string['lessonscope'] = 'Statische lesinhoud en oplossingen. Beschikbare pogingsrapporten staan hieronder. Dynamische sprongen worden beschreven, niet uitgevoerd.';
$string['backtolesson'] = 'Terug naar de les';
$string['lessonunknown'] = 'Onbekend paginatype ({$a})';
$string['lessonmultiple'] = 'Meerdere antwoorden kunnen worden geselecteerd.';
$string['lessonessay'] = 'Essayvraag: handmatige beoordeling in Moodle. Zie toegankelijke pogingsrapporten voor inzendingen.';
$string['lessonpair'] = 'Koppelingspaar';
$string['lessonanswer'] = 'Antwoord / navigatie';
$string['lessoncorrect'] = 'Correcte koppeling';
$string['lessonincorrect'] = 'Onjuiste koppeling';
$string['lessonmatch'] = 'Bijbehorende waarde';
$string['lessonfeedback'] = 'Feedback';
$string['lessonscore'] = 'Ingestelde score: {$a}';
$string['lessonempty'] = 'Deze les bevat geen pagina’s.';
$string['lessonend'] = 'Einde van de les';
$string['lessondynamic'] = 'Dynamische bestemming afhankelijk van de Moodle-poging. Gebruik de lesindex om de pagina’s te bekijken.';
$string['lessongoto'] = 'Ga naar: {$a}';
$string['lessonmissing'] = 'Bestemming niet beschikbaar in deze les.';

// Lesson attempt reports.
$string['lessonattempts'] = 'Pogingen van studenten';
$string['lessonreportsdenied'] = 'Pogingsrapporten niet opgenomen: rapportagerechten vereist.';
$string['lessonattemptnumber'] = 'Poging {$a}';
$string['lessoncompleted'] = 'Voltooid';
$string['lessonincomplete'] = 'Niet voltooid';
$string['lessonstarted'] = 'Gestart';
$string['lessonlastactivity'] = 'Laatst geregistreerde activiteit';
$string['lessonrecordedgrade'] = 'Opgeslagen lescijfer: {$a}%';
$string['lessonanswered'] = 'Beantwoord';
$string['lessonvisited'] = 'Geregistreerde bezoeken aan inhoudspagina’s';
$string['lessonreportsscope'] = 'Rapporten bevatten actieve ingeschreven gebruikers zichtbaar in de huidige groepscontext. Elke poging is afzonderlijk; cijfers worden niet herberekend.';
$string['lessonnoattempts'] = 'Geen zichtbare geregistreerde pogingen.';
$string['lessonunreadable'] = 'Het geregistreerde antwoord kon niet worden gedecodeerd.';
$string['lessonungraded'] = 'Nog niet beoordeeld';
$string['lessonearnedscore'] = 'Toegekende score: {$a}';
$string['lessonanswercorrect'] = 'Als correct geregistreerd';
$string['lessonanswerincorrect'] = 'Als onjuist geregistreerd';

$string['backtocourseindex'] = 'Terug naar de cursusindex';

$string['archivegenerated'] = 'Archief gegenereerd';
$string['quizarchivescope'] = 'Statische vragenlijst en toegankelijke opgeslagen pogingen. Antwoorden kunnen niet worden ingediend; cijfers worden niet herberekend.';
$string['quizquestionnaire'] = 'Vragenlijst en oplossingen';
$string['quizattempts'] = 'Opgeslagen pogingen';
$string['quizstoredmarks'] = 'Opgeslagen punten: {$a}';
$string['quiznoattempts'] = 'Geen pogingen beschikbaar voor inzage met je huidige rechten.';
$string['quizslot'] = 'Vraagpositie {$a}';
$string['quizrandomslot'] = 'Willekeurige vraagpositie. De werkelijk gekozen vragen worden in toegankelijke pogingen bewaard. Er wordt geen nieuwe selectie uitgevoerd.';
$string['quizquestionunavailable'] = 'Vraag niet beschikbaar of geen toestemming om deze vraag te gebruiken.';
$string['quizunsupported'] = 'Vraagtype nog niet ondersteund voor volledige offline weergave: {$a}. Beschikbare samenvattingen van de poging volgen.';
$string['backtoquiz'] = 'Terug naar de test';

$string['unsupportedactivity'] = 'Niet ondersteund';
$string['exportnotpermitted'] = 'Onvoldoende rechten voor export';
