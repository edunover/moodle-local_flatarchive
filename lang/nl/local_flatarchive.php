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
$string['nothingselected'] = 'Selecteer minstens één activiteit of cursusoptie.';
$string['status_idle'] = 'Klaar';
$string['status_running'] = 'Bezig';
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
$string['invalidselection'] = 'De selectie bevat een niet-ondersteunde, ontoegankelijke bron of een bron uit een andere cursus.';

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

// Database archive.
$string['datanotapproved'] = 'Niet goedgekeurd';
$string['backtodata'] = 'Terug naar de databank';
$string['dataviewentry'] = 'Bekijk item';
$string['dataempty'] = 'Geen items beschikbaar voor export.';
$string['datarequiredentries'] = 'Cursusinstelling: {$a} bijdragen vereist voordat andere items zichtbaar zijn. Deze voorwaarde geldt niet voor het archief.';

// Archived activity settings.
$string['archivesettings'] = 'Instellingen';
$string['settingssnapshot'] = 'Configuratie vastgelegd op:';
$string['settingsscope'] = 'Documentaire momentopname. Aangepast JavaScript wordt vermeld maar niet gekopieerd. Interne configuratie en parameters van externe veldtypen zijn niet opgenomen.';
$string['settingsvisibility'] = 'Zichtbaarheid';
$string['settingsvisible'] = 'Zichtbaar op de cursuspagina';
$string['settingshidden'] = 'Verborgen';
$string['settingsstealth'] = 'Verborgen maar beschikbaar';
$string['settingsshowdescription'] = 'Beschrijving op cursuspagina tonen';
$string['settingsgroupforced'] = 'Groepsmodus afgedwongen door cursus';
$string['settingsaccess'] = 'Toegangsbeperkingen';
$string['settingscompletion'] = 'Voltooiingsregistratie';
$string['settingscompletion0'] = 'Uitgeschakeld';
$string['settingscompletion1'] = 'Handmatig';
$string['settingscompletion2'] = 'Automatisch';
$string['settingscompletionview'] = 'Bekijken vereist';
$string['settingscompletiongrade'] = 'Beoordeling vereist';
$string['settingscompletionexpected'] = 'Verwachte voltooiingsdatum';
$string['settingsrequiredentries'] = 'Vereiste items (oude instelling)';
$string['settingsrequiredentriestoview'] = 'Bijdragen vereist vóór bekijken';
$string['settingsmaxentries'] = 'Maximum aantal items per deelnemer';
$string['settingscompletionentries'] = 'Items vereist voor voltooiing';
$string['settingsrssarticles'] = 'Items in RSS-feed';
$string['settingstimecreated'] = 'Aanmaakdatum';
$string['settingstimemodified'] = 'Wijzigingsdatum';
$string['settingsapprovalstatus'] = 'Goedkeuringsstatus';
$string['settingssort'] = 'Standaard sorteerveld';
$string['settingssortdirection'] = 'Sorteerrichting';
$string['settingsascending'] = 'Oplopend';
$string['settingsdescending'] = 'Aflopend';
$string['settingsrating'] = 'Samenvoeging van beoordelingen';
$string['settingsrating0'] = 'Geen beoordelingen';
$string['settingsrating1'] = 'Gemiddelde';
$string['settingsrating2'] = 'Aantal';
$string['settingsrating3'] = 'Maximum';
$string['settingsrating4'] = 'Minimum';
$string['settingsrating5'] = 'Som';
$string['settingsscale'] = 'Schaal of maximumcijfer';
$string['settingsratingfrom'] = 'Beoordelingen vanaf';
$string['settingsratingto'] = 'Beoordelingen tot';
$string['settingsgradepass'] = 'Vereist cijfer om te slagen';
$string['settingsgradecategory'] = 'Beoordelingscategorie';
$string['settingsfields'] = 'Velddefinities';
$string['settingsfieldtype'] = 'Veldtype';
$string['settingsfieldrequired'] = 'Verplicht veld';
$string['settingsfieldoptions'] = 'Beschikbare keuzes';
$string['settingsautolink'] = 'Automatische koppelingen';
$string['settingscolumns'] = 'Editorbreedte (kolommen)';
$string['settingsrows'] = 'Editorhoogte (rijen)';
$string['settingsmaxbytes'] = 'Maximale bestandsgrootte (bytes; 0 = overgenomen)';
$string['settingswidth'] = 'Afbeeldingsbreedte (pixels)';
$string['settingsheight'] = 'Afbeeldingshoogte (pixels)';
$string['settingsthumbwidth'] = 'Miniatuurbreedte (pixels)';
$string['settingsthumbheight'] = 'Miniatuurhoogte (pixels)';
$string['settingslinktext'] = 'Linktekst';
$string['settingsnewwindow'] = 'Openen in een nieuw venster';
$string['settingstemplates'] = 'Weergavesjablonen (bron)';
$string['settingslisttemplateheader'] = 'Lijstkop';
$string['settingslisttemplate'] = 'Lijstitem';
$string['settingslisttemplatefooter'] = 'Lijstvoet';
$string['settingssingletemplate'] = 'Enkel item';
$string['settingsaddtemplate'] = 'Invoerformulier';
$string['settingsasearchtemplate'] = 'Geavanceerd zoeken';
$string['settingsrsstemplate'] = 'RSS-item';
$string['settingsrsstitletemplate'] = 'RSS-titel';
$string['settingscsstemplate'] = 'Aangepaste CSS';
$string['settingsjavascript'] = 'Aangepast JavaScript aanwezig';

// Course and activity settings snapshots.
$string['settingsgeneralscope'] = 'Momentopname van standaardinstellingen van de cursus of activiteit. Wachtwoorden, geheimen, individuele uitzonderingen en instellingen van externe plugins zijn niet opgenomen.';
$string['settingsbackactivity'] = 'Terug naar de activiteit';
$string['settingspasswordset'] = 'Wachtwoord ingesteld (waarde weggelaten)';
$string['settingscourselang'] = 'Verplichte taal';
$string['settingscoursenews'] = 'Aantal mededelingen';
$string['settingscoursegrades'] = 'Cijferlijst aan studenten tonen';
$string['settingscoursereports'] = 'Activiteitenrapporten tonen';
$string['settingscoursedates'] = 'Activiteitsdatums tonen';
$string['settingscoursecompletion'] = 'Voltooiingsvoorwaarden tonen';
$string['settingsreviewduring'] = 'Tijdens de poging';
$string['settingsreviewimmediately'] = 'Direct na de poging';
$string['settingsreviewopen'] = 'Later, terwijl de test nog open is';
$string['settingsreviewclosed'] = 'Nadat de test is gesloten';
$string['settingsreviewattempt'] = 'De poging';
$string['settingsreviewcorrectness'] = 'Of het antwoord juist is';
$string['settingsreviewmarks'] = 'Cijfers';
$string['settingsreviewspecificfeedback'] = 'Specifieke feedback';
$string['settingsreviewgeneralfeedback'] = 'Algemene feedback';
$string['settingsreviewrightanswer'] = 'Juiste antwoord';
$string['settingsreviewoverallfeedback'] = 'Globale feedback';
$string['settingfield_display'] = 'Weergavemodus';
$string['settingfield_multiple_submit'] = 'Meerdere inzendingen toestaan';
$string['settingfield_publish_stats'] = 'Analyse publiceren';
$string['settingfield_timeopen'] = 'Openingsdatum';
$string['settingfield_timeclose'] = 'Sluitingsdatum';
$string['settingfield_showresults'] = 'Wanneer resultaten worden getoond';
$string['settingfield_maxbytes'] = 'Maximale bestandsgrootte';
$string['settingfield_grade'] = 'Maximaal cijfer';
$string['settingfield_usemaxgrade'] = 'Hoogste cijfer van pogingen gebruiken';
$string['settingfield_maxattempts'] = 'Maximaal aantal pogingen per vraag';
$string['settingfield_feedback'] = 'Standaardfeedback tonen';
$string['settingfield_minquestions'] = 'Minimaal aantal vragen';
$string['settingfield_maxpages'] = 'Maximaal aantal pagina’s';
$string['settingfield_displayleft'] = 'Menu tonen';
$string['settingfield_attemptonlast'] = 'Elke poging bouwt voort op de vorige';
$string['settingfield_questiondecimalpoints'] = 'Decimalen in vraagcijfers';
$string['settingfield_allowofflineattempts'] = 'Offlinepogingen toestaan';
$string['settingsdisplayauto'] = 'Automatisch';
$string['settingsdisplayembed'] = 'Inbedden';
$string['settingsdisplayframe'] = 'Frame';
$string['settingsdisplaynew'] = 'Nieuw venster';
$string['settingsdisplaydownload'] = 'Download afdwingen';
$string['settingsdisplayopen'] = 'Openen';
$string['settingsdisplaypopup'] = 'Pop-up';
$string['settingsprintintro'] = 'Beschrijving tonen';
$string['settingsprintheading'] = 'Titel tonen';
$string['settingsshowsize'] = 'Bestandsgrootte tonen';
$string['settingsshowtype'] = 'Bestandstype tonen';
$string['settingsshowdate'] = 'Wijzigingsdatum van het bestand tonen';
$string['settingspopupwidth'] = 'Breedte van pop-up (pixels)';
$string['settingspopupheight'] = 'Hoogte van pop-up (pixels)';
$string['settingfield_completiontimespent'] = 'Minimale bestede tijd voor voltooiing';
$string['settingsnavtoc'] = 'Alleen inhoudsopgave (oude navigatie)';
$string['settingsnavimages'] = 'Afbeeldingen (oude navigatie)';
$string['settingsnavtext'] = 'Tekst (oude navigatie)';
$string['settingsemptyfield'] = 'Leeg veld';

$string['urlexternalnotice'] = 'Externe bestemming: een internetverbinding kan nodig zijn. De website is niet opgenomen in dit archief.';
$string['urlparametersnotice'] = 'Dynamische Moodle-parameters worden niet aan deze gearchiveerde link toegevoegd.';
$string['assignarchivescope'] = 'Statische kopie: online tekst, ingeleverde bestanden, opdrachtcijfers en feedbacktekst/bestanden. Geavanceerde beoordelingsformulieren en PDF-annotaties worden niet overgenomen.';
$string['assignanonymous'] = 'Deelnemer {$a}';
$string['assignrecords'] = 'Opgeslagen inzendingen en beoordelingen';
$string['assignnorecords'] = 'Geen inzending of beoordeling beschikbaar voor deze export met jouw rechten.';
$string['assignattempt'] = 'Poging {$a}';
$string['assigngroupsubmission'] = 'Groepsinzending';
$string['assignnotgraded'] = 'Niet beoordeeld';
$string['assignunsupported'] = 'Inhoud niet geëxporteerd voor deze uitbreiding: {$a}';
$string['assigngrade'] = 'Opdrachtcijfer';

// Native H5P static export.
$string['h5pinvalid'] = 'Het H5P-pakket ontbreekt, is ongeldig of overschrijdt de archieflimieten.';
$string['h5pstaticnotice'] = 'Statische leeskopie: oefeningen kunnen hier niet worden ingevuld. Oplossingen en feedback blijven ' .
    'bewaard. Het oorspronkelijke H5P-pakket is apart bijgevoegd; niet-ondersteunde elementen worden ' .
    'aangegeven.';
$string['h5psource'] = 'Oorspronkelijk H5P-pakket downloaden';
$string['h5punsupported'] = 'Element niet omgezet naar statische HTML: {$a}. Raadpleeg het oorspronkelijke H5P-pakket.';
$string['h5pmediaunavailable'] = 'Media niet ingesloten: {$a}. Externe media vereisen internet; andere bestanden blijven in het ' .
    'oorspronkelijke pakket.';
$string['h5pchoices'] = 'Mogelijke antwoorden';
$string['h5psolutions'] = 'Oplossingen';
$string['h5pdistractors'] = 'Extra afleidingswoorden';
$string['h5pwords'] = 'Woorden / toegestane alternatieven';
$string['h5ptrue'] = 'Waar';
$string['h5pfalse'] = 'Onwaar';
$string['h5pelements'] = 'Te plaatsen elementen';
$string['h5pzone'] = 'Zone {$a}';
$string['h5pslide'] = 'Dia {$a}';
$string['h5pquestion'] = 'Vraag {$a}';
$string['h5pslidelink'] = 'Ga naar dia: {$a}';
$string['h5pjumpcorrect'] = 'Correct antwoord: ga naar {$a}';
$string['h5pjumpwrong'] = 'Onjuist antwoord: ga naar {$a}';
$string['h5pjump'] = 'Ga naar {$a}';
$string['h5prequirecompletion'] = 'Deze interactie moest oorspronkelijk worden voltooid om verder te gaan.';
$string['h5ptip'] = 'Hint';
$string['h5pchosenfeedback'] = 'Feedback bij selectie';
$string['h5pnotchosenfeedback'] = 'Feedback indien niet geselecteerd';
$string['h5pfeedbackoncorrect'] = 'Feedback bij een correct antwoord';
$string['h5pfeedbackonincorrect'] = 'Feedback bij een onjuist antwoord';
$string['h5pfeedbackonwrong'] = 'Feedback bij een onjuist antwoord';
$string['h5pcredits'] = 'Naamsvermelding en licentie';
$string['h5pback'] = 'Terug naar H5P-activiteit';
$string['h5pcontentsettings'] = 'H5P-inhoudsinstellingen (opgeslagen waarden)';
$string['h5pattempts'] = 'Opgeslagen pogingen';
$string['h5pattemptnotice'] = 'Alleen opgeslagen Moodle-rapporten die voor de exporterende gebruiker toegankelijk zijn, worden ' .
    'opgenomen. Eerdere antwoorden worden niet opnieuw berekend met de huidige inhoud.';
$string['h5pnoreports'] = 'Geen toegankelijk opgeslagen rapport.';
$string['h5pattempt'] = 'Poging {$a}';
$string['h5prawresponse'] = 'Opgeslagen antwoord (oorspronkelijke xAPI-codering)';
$string['h5pscore'] = 'Opgeslagen score';
$string['h5pcompletion'] = 'Voltooid';
$string['h5psuccess'] = 'Geslaagd';
$string['h5pyes'] = 'Ja';
$string['h5pno'] = 'Nee';
$string['h5punknown'] = 'Niet opgeslagen';
$string['h5pduration'] = 'Opgeslagen duur: {$a} seconden';
$string['settingfield_h5ptracking'] = 'Pogingen bijhouden';
$string['settingfield_h5pgrademethod'] = 'Beoordelingsmethode (Moodle-waarde)';
$string['settingfield_h5previewmode'] = 'Beoordelingsmodus (Moodle-waarde)';
$string['settingfield_h5pdisplayoptions'] = 'Weergaveopties (Moodle-bitmasker)';
$string['h5punresolvedlink'] = 'Niet-opgeloste link: {$a}';

// Workshop archive.
$string['wsscope'] = 'Statische Workshopopname. Opgeslagen cijfers blijven behouden zonder ' .
    'herberekening. Namen en rapporten volgen Moodle-rechten. Originele bestanden ' .
    'kunnen hun auteurs identificeren.';
$string['wsinstructauthors'] = 'Instructies voor inzendingen';
$string['wsinstructreviewers'] = 'Beoordelingsinstructies';
$string['wsconclusion'] = 'Conclusie';
$string['wsform'] = 'Beoordelingsformulier';
$string['wssubmissions'] = 'Inzendingen';
$string['wsexample'] = 'Voorbeeldinzending';
$string['wsback'] = 'Terug naar Workshop';
$string['wsauthor'] = 'Auteur';
$string['wsreviewer'] = 'Beoordelaar';
$string['wscreated'] = 'Aangemaakt';
$string['wsmodified'] = 'Laatst gewijzigd';
$string['wslate'] = 'Late inzending';
$string['wspublished'] = 'Gepubliceerde inzending';
$string['wssubmissiongrade'] = 'Cijfer voor inzending';
$string['wsassessmentgrade'] = 'Cijfer voor beoordelingskwaliteit';
$string['wsoverride'] = 'Door docent vervangen cijfer';
$string['wsfeedbackauthor'] = 'Feedback voor de auteur';
$string['wsfeedbackreviewer'] = 'Docentfeedback voor de beoordelaar';
$string['wsassessment'] = 'Beoordeling {$a}';
$string['wsassessments'] = 'Beoordelingen';
$string['wsrestricted'] = 'Rapporten weggelaten: vereiste Moodle-rechten ontbreken.';
$string['wsnosubmissions'] = 'Geen toegankelijke inzending.';
$string['wsweight'] = 'Weging';
$string['wspending'] = 'Toegewezen, nog niet beoordeeld';
$string['wsunsupported'] = 'Beoordelingsstrategie niet omgezet';
$string['wsstrategy'] = 'Beoordelingsstrategie';
$string['wscriterion'] = 'Criterium {$a}';
$string['wsmatchedlevel'] = 'Komt overeen met opgeslagen score';
$string['wsrecorded'] = 'Opgeslagen antwoord';
$string['wsmaximum'] = 'Maximale punten';
$string['wsrecordedvalue'] = 'Opgeslagen numerieke waarde';
$string['wsnotgraded'] = 'Niet beoordeeld';
$string['wscomment'] = 'Opmerking';
$string['wsnoanswer'] = 'Geen opgeslagen antwoord voor dit criterium.';
$string['wserrormap'] = 'Omzetting van fouten naar cijfer';
$string['wsaggregate'] = 'Geaggregeerde cijfers voor beoordelingskwaliteit';
$string['wsanonymous'] = 'Anonieme deelnemer';
$string['settingfield_wsphase'] = 'Fase (Moodle-waarde)';
$string['settingfield_wsstrategy'] = 'Strategie';
$string['settingfield_wsgrade'] = 'Maximumcijfer inzending';
$string['settingfield_wsgradinggrade'] = 'Maximumcijfer beoordelingen';
$string['settingfield_wsgradedecimals'] = 'Decimalen';
$string['settingfield_wsuseexamples'] = 'Voorbeeldinzendingen';
$string['settingfield_wsuseselfassessment'] = 'Zelfbeoordeling';
$string['settingfield_wssubmissionstart'] = 'Inzenden vanaf';
$string['settingfield_wssubmissionend'] = 'Inzenddeadline';
$string['settingfield_wsassessmentstart'] = 'Beoordelen vanaf';
$string['settingfield_wsassessmentend'] = 'Beoordelingsdeadline';
$string['settingfield_wsphaseswitchassessment'] = 'Automatische faseovergang';
$string['settingfield_wslatesubmissions'] = 'Late inzendingen toegestaan';
$string['settingfield_wssubmissiontypetext'] = 'Modus online tekst (Moodle-waarde)';
$string['settingfield_wssubmissiontypefile'] = 'Modus bestandsinzending (Moodle-waarde)';
$string['settingfield_wsnattachments'] = 'Maximum bijlagen';
$string['settingfield_wssubmissionfiletypes'] = 'Bestandstypen inzendingen';
$string['settingfield_wsmaxbytes'] = 'Maximale bestandsgrootte';
$string['settingfield_wsexamplesmode'] = 'Voorbeeldmodus (Moodle-waarde)';
$string['settingfield_wsoverallfeedbackmode'] = 'Algemene feedbackmodus (Moodle-waarde)';
$string['settingfield_wsoverallfeedbackfiles'] = 'Feedbackbijlagen';
$string['settingfield_wsoverallfeedbackfiletypes'] = 'Feedbackbestandstypen';
$string['settingfield_wsoverallfeedbackmaxbytes'] = 'Maximale grootte feedbackbestanden';

// Portable course and activity report navigation.
$string['archivereports'] = 'Rapporten';
$string['backtoreports'] = 'Terug naar rapporten';
$string['reportscoursenavigation'] = 'Cursusinstellingen en rapporten';
$string['reportscourse'] = 'Cursusrapporten';
$string['reportsactivities'] = 'Rapporten van geëxporteerde activiteiten';
$string['reportsscope'] = 'Cursusrapporten omvatten de volledige toegankelijke cursus, onafhankelijk van de geselecteerde inhoud. Activiteitsrapporten zijn beschikbaar vanuit elke geëxporteerde activiteit. Dit is een statische momentopname.';
$string['reportssnapshot'] = 'Momentopname gemaakt op: {$a}';
$string['reportsnoactivities'] = 'Geen geselecteerde activiteit bevat een exporteerbaar rapportonderdeel.';
$string['reportsgrades'] = 'Cijferboek — opgeslagen cijfers';
$string['reportscoursecompletion'] = 'Cursusvoltooiing';
$string['reportsprogress'] = 'Activiteitsvoltooiing';
$string['reportsparticipation'] = 'Cursusdeelname';
$string['reportsdenied'] = 'Niet geëxporteerd: vereiste rapportrechten ontbreken.';
$string['reportsgroupdenied'] = 'Niet geëxporteerd: geen toegestane groep geselecteerd.';
$string['reportsallgroups'] = 'Groepsbereik: alle toegestane deelnemers.';
$string['reportsgroup'] = 'Groepsbereik: {$a}';
$string['reportsparticipantscope'] = 'Alleen actief ingeschreven deelnemers. Cijfers en deelname gebruiken de ingestelde cijferboekrollen; voltooiing volgt de Moodle-rechten voor voltooiingsrapporten.';
$string['reportsgradesscope'] = 'Opgeslagen eindcijfers blijven behouden zonder herberekening, met de weergave van het cijferonderdeel. Ontbrekende cijfers worden als — getoond.';
$string['reportshiddentotals'] = 'Verborgen cijfers zijn uitgesloten. Cursus- en categorietotalen zijn weggelaten omdat zij verborgen cijfers indirect kunnen onthullen.';
$string['reportscompletiondisabled'] = 'Voltooiingsregistratie is uitgeschakeld of er zijn geen cursusvoltooiingscriteria ingesteld.';
$string['reportscomplete'] = 'Voltooid';
$string['reportsincomplete'] = 'Niet voltooid';
$string['reportspassed'] = 'Voltooid — geslaagd';
$string['reportsfailed'] = 'Voltooid — niet geslaagd';
$string['reportsempty'] = 'Geen rapportgegevens beschikbaar binnen dit bereik.';
$string['reportsnologs'] = 'Geen ondersteunde SQL-loglezer is ingeschakeld. Verouderde en externe logs worden niet meegenomen; tellingen kunnen niet worden gemaakt.';
$string['reportsparticipationscope'] = 'Tellingen gebruiken bewaarde web-/webservicegebeurtenissen op deelnemersniveau tot het moment van de opname, uit de SQL-lezer die Moodle selecteert. Raadplegingen tellen leesacties; bijdragen tellen aanmaak-, wijzigings- en verwijderacties. Gebeurtenissen binnen dezelfde seconde tellen eenmaal per categorie. Verwijderde, oude en externe logs worden niet hersteld. Nul bewijst geen afwezigheid van deelname. IP-adressen en ruwe logs worden niet geëxporteerd.';
$string['reportsviews'] = 'Raadplegingen';
$string['reportsposts'] = 'Bijdragen';

$string['archiveparticipants'] = 'Deelnemers';
$string['archivegradebook'] = 'Cijferboek';
$string['archiveparticipantsscope'] = 'Ingeschreven deelnemers, inclusief inactieve inschrijvingen, binnen de geselecteerde groep. Cursusrollen en overgeërfde rollen gebruiken de namen die in deze cursus zijn ingesteld.';
$string['archiveparticipantactive'] = 'Actief';
$string['archiveparticipantinactive'] = 'Inactief';
$string['archiveparticipantstatus'] = 'Inschrijvingsstatus';

$string['activitynavigation'] = 'Activiteitsinstellingen en rapporten';

$string['coursesettingshelp'] = 'Schakel Settings uit om cursus- en activiteitsinstellingen weg te laten. Reports betreft cursusrapporten; activiteitsrapporten blijven bij geselecteerde activiteiten.';

$string['coursegrades'] = 'Cijfers';

$string['stylepreset'] = 'Basisstijl';
$string['stylepreset_help'] = 'Een stijl kiezen vervangt het CSS-veld hieronder. Pas het aan en sla de wijzigingen op. Geen maakt het veld leeg; de basisopmaak blijft behouden. Het heropenen van deze pagina overschrijft nooit opgeslagen CSS.';
$string['customcss'] = 'Institutionele CSS';
$string['customcss_help'] = 'Deze stylesheet wordt na de basisopmaak opgenomen in gegenereerde archieven. De Moodle-site verandert niet. Gebruik lokale stijlen voor offline archieven.';
$string['style_none'] = 'Geen';
$string['style_institutional'] = 'Institutioneel blauw';
$string['style_modern'] = 'Modern groen';
$string['style_classic'] = 'Klassieke typografie';
