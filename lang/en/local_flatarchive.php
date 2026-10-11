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
$string['visibility_stealth'] = 'hidden but available';
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

$string['wikiscope'] = 'Latest revisions of readable pages, files and comments. Revision history is excluded; external resources remain external.';
$string['wikispace'] = 'Wiki space {$a}';
$string['wikiunknowngroup'] = 'Unknown group';
$string['wikiunknownauthor'] = 'Unknown or deleted author';
$string['wikifirstpage'] = 'Start page';
$string['wikiempty'] = 'No readable pages.';
$string['wikiattachments'] = 'Files in this wiki space';
$string['backtowiki'] = 'Back to wiki';
$string['wikilastauthor'] = 'Last revision by';
$string['wikicomments'] = 'Comments';
$string['wikinotarchived'] = 'Page not archived';

$string['archive_success_size'] = 'Your archive has been generated. ({$a})';

$string['feedbackscope'] = 'Static questionnaire and permitted submitted responses. Drafts are excluded. Anonymous responses have no author or submission date. Free-text answers are preserved as written.';

$string['feedbackquestions'] = 'Questionnaire';

$string['feedbackcondition'] = 'Displayed when “{$a->question}” equals “{$a->value}”.';

$string['feedbackempty'] = 'This questionnaire is empty.';

$string['feedbackaftersubmit'] = 'Completion message';

$string['feedbackresponses'] = 'Individual responses';

$string['feedbackresponsesrestricted'] = 'Individual responses are not included: permission to view reports is required.';

$string['feedbackresponse'] = 'Response {$a}';

$string['backtofeedback'] = 'Back to Feedback';

$string['feedbacknoresponses'] = 'No submitted responses are accessible in this course and your groups.';

$string['feedbackunsupported'] = 'This question type is not supported by the archive.';

$string['choicemultiple'] = 'Multiple options may be selected.';
$string['choicesingle'] = 'Only one option may be selected.';
$string['choiceoptions'] = 'Options';
$string['choicelimit'] = 'Maximum responses: {$a}';
$string['choiceresults'] = 'Results';
$string['choicerestricted'] = 'Results are not available for export with your current permissions or publication settings.';
$string['choicescope'] = 'Snapshot of submitted responses for the current Moodle group and enrolment scope.'
    . ' Non-respondents and response dates are not included.';
$string['choicecount'] = 'Responses: {$a}';

$string['forumscope'] = 'Only visible messages are included. Replies are grouped below their parent message.';
$string['backtoforum'] = 'Back to forum';
$string['forumunknownauthor'] = 'Unknown author';
$string['forummodified'] = 'Modified: {$a}';
$string['forumprivate'] = 'Private reply';
$string['forumparent'] = 'View parent message';
$string['forumattachments'] = 'Attachments';
$string['forumempty'] = 'No accessible messages.';

$string['forumpinned'] = 'Pinned';

$string['archivetags'] = 'Tags';
$string['archiveactivitytags'] = 'Activity tags';
$string['archivecoursetags'] = 'Course tags';

// Static Lesson export.
$string['lessonscope'] = 'Static teaching content and solutions. Available attempt reports are listed below. Dynamic jumps are described, not executed.';
$string['backtolesson'] = 'Back to lesson';
$string['lessonunknown'] = 'Unknown page type ({$a})';
$string['lessonmultiple'] = 'Multiple answers may be selected.';
$string['lessonessay'] = 'Essay question: manual assessment in Moodle. See accessible attempt reports for submissions.';
$string['lessonpair'] = 'Matching pair';
$string['lessonanswer'] = 'Answer / navigation';
$string['lessoncorrect'] = 'Correct matching result';
$string['lessonincorrect'] = 'Incorrect matching result';
$string['lessonmatch'] = 'Corresponding value';
$string['lessonfeedback'] = 'Feedback';
$string['lessonscore'] = 'Configured score: {$a}';
$string['lessonempty'] = 'This lesson has no pages.';
$string['lessonend'] = 'End of lesson';
$string['lessondynamic'] = 'Dynamic destination depending on the Moodle attempt. Use the lesson index to explore the pages.';
$string['lessongoto'] = 'Go to: {$a}';
$string['lessonmissing'] = 'Destination unavailable in this lesson.';

// Lesson attempt reports.
$string['lessonattempts'] = 'Student attempts';
$string['lessonreportsdenied'] = 'Attempt reports are not included: report permission is required.';
$string['lessonattemptnumber'] = 'Attempt {$a}';
$string['lessoncompleted'] = 'Completed';
$string['lessonincomplete'] = 'Not completed';
$string['lessonstarted'] = 'Started';
$string['lessonlastactivity'] = 'Last recorded activity';
$string['lessonrecordedgrade'] = 'Stored lesson grade: {$a}%';
$string['lessonanswered'] = 'Answered';
$string['lessonvisited'] = 'Recorded content page visits';
$string['lessonreportsscope'] = 'Reports include active enrolled users visible in the current group context. Each retry is separate; stored grades are not recalculated.';
$string['lessonnoattempts'] = 'No visible recorded attempts.';
$string['lessonunreadable'] = 'Recorded response could not be decoded.';
$string['lessonungraded'] = 'Not yet graded';
$string['lessonearnedscore'] = 'Awarded score: {$a}';
$string['lessonanswercorrect'] = 'Recorded as correct';
$string['lessonanswerincorrect'] = 'Recorded as incorrect';

$string['backtocourseindex'] = 'Back to course index';
