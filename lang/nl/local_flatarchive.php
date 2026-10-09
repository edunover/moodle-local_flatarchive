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
