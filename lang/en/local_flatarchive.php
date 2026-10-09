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
 * English language strings.
 *
 * @package local_flatarchive
 * @copyright 2025 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Flat archive';
$string['flatarchive'] = 'Flat archive';
$string['backtoflatarchive'] = 'Back to Flat Archive';
$string['archivecourse'] = 'Archive this course';
$string['selectall'] = 'Select all';
$string['deselectall'] = 'Deselect all';
$string['archiveselection'] = 'Archive selection';
$string['loading'] = 'Loading';
$string['backendnotready'] = 'Backend not connected yet. Next step: implement get_tree and start_job.';
$string['nothingselected'] = 'Select at least one resource.';
$string['status_idle'] = 'Ready';
$string['status_running'] = 'In progress';
$string['status_done'] = 'Done';
$string['status_failed'] = 'Failed';
$string['downloadzip'] = 'Download ZIP';
$string['hidden'] = 'hidden';
$string['archive_description'] = 'This tool allows you to archive your course content as static HTML pages.';
$string['generate_html_archive'] = 'Generate HTML archive';
$string['archive_success'] = 'Your archive has been generated.';
$string['noselection'] = 'No content selected.';
$string['archive_success_download'] = 'Download ZIP archive';
$string['archive_downloaded_deleted'] = 'Archive downloaded and deleted.';
$string['processing_archive'] = 'Processing archive';
$string['about_course'] = 'About this course';
$string['coursenotfound'] = 'Course not found.';
$string['footer_copyright'] = 'Flatarchive {$a} &#169; EDUNOVER';
$string['visibility_hidden'] = 'hidden';
$string['visibility_stealth'] = 'hidden from course page';
$string['visibility_section_hidden'] = 'hidden section';
$string['visibility_chapter_hidden'] = 'hidden chapter';
$string['noresources'] = 'No supported resources are available to archive.';
$string['archivefailed'] = 'The archive could not be created. Check available disk space and file permissions.';
$string['invalidselection'] = 'The selection contains an unsupported resource or a resource outside this course.';

$string['flatarchive:archive'] = 'Archive course resources';

$string['resourcefilenotfound'] = 'The file resource "{$a}" has no file to archive.';

$string['archiveunavailable'] = 'Archive no longer available';

$string['archiveunavailable_help'] = 'This temporary archive is no longer available. Return to your course and generate a new archive.';

$string['downloadavailability'] = 'You can download this archive again for one hour after generation, while signed in to this session.';

$string['foldercontents'] = 'Files and subfolders';
$string['folderempty'] = 'This folder is empty.';

$string['glossaryscope'] = 'Only approved entries are included. Readable comments and author names are archived; ratings and user profiles are excluded.';
$string['glossaryexcluded'] = 'Pending entries excluded: {$a}.';
$string['glossaryaliases'] = 'Aliases';
$string['glossarycategories'] = 'Categories';
$string['glossaryattachments'] = 'Attachments';
$string['glossaryempty'] = 'No approved entries.';
$string['glossaryentries'] = 'Glossary entries';
$string['backtoglossary'] = 'Back to glossary';
$string['glossaryauthor'] = 'Author';
$string['glossarycomments'] = 'Comments';
$string['glossaryunknownauthor'] = 'Unknown or deleted author';
