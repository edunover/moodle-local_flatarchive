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
$string['processing_archive'] = 'Processing archive';
$string['about_course'] = 'About this course';
$string['coursenotfound'] = 'Course not found.';
$string['footer_copyright'] = 'Flatarchive {$a} &#169; EDUNOVER';
$string['visibility_hidden'] = 'hidden';
$string['visibility_stealth'] = 'hidden but available';
$string['visibility_section_hidden'] = 'hidden section';
$string['visibility_chapter_hidden'] = 'hidden chapter';
$string['noresources'] = 'No activities or resources are selectable for this export.';
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

$string['archivegenerated'] = 'Archive generated';
$string['quizarchivescope'] = 'Static questionnaire and accessible recorded attempts. No answers can be submitted; marks are not recalculated.';
$string['quizquestionnaire'] = 'Questionnaire and solutions';
$string['quizattempts'] = 'Recorded attempts';
$string['quizstoredmarks'] = 'Stored points: {$a}';
$string['quiznoattempts'] = 'No attempts available for review with your current permissions.';
$string['quizslot'] = 'Question position {$a}';
$string['quizrandomslot'] = 'Random question position. The questions actually drawn are preserved in accessible attempts. No new draw is performed.';
$string['quizquestionunavailable'] = 'Question unavailable or permission to use this question is missing.';
$string['quizunsupported'] = 'Question type not yet supported for full offline rendering: {$a}. Any available attempt summaries follow.';
$string['backtoquiz'] = 'Back to quiz';

$string['unsupportedactivity'] = 'Not supported';
$string['exportnotpermitted'] = 'Insufficient export permissions';

// Database archive.
$string['datanotapproved'] = 'Not approved';
$string['backtodata'] = 'Back to database';
$string['dataviewentry'] = 'View entry';
$string['dataempty'] = 'No entries available for export.';
$string['datarequiredentries'] = 'Course setting: {$a} entries required before viewing other entries. This requirement is not applied to the archive.';

// Archived activity settings.
$string['archivesettings'] = 'Settings';
$string['settingssnapshot'] = 'Configuration recorded on:';
$string['settingsscope'] = 'Documentary snapshot. Custom JavaScript is indicated but not copied. Internal configuration and third-party field parameters are not included.';
$string['settingsvisibility'] = 'Visibility';
$string['settingsvisible'] = 'Visible on the course page';
$string['settingshidden'] = 'Hidden';
$string['settingsstealth'] = 'Hidden but available';
$string['settingsshowdescription'] = 'Show description on course page';
$string['settingsgroupforced'] = 'Group mode forced by course';
$string['settingsaccess'] = 'Access restrictions';
$string['settingscompletion'] = 'Completion tracking';
$string['settingscompletion0'] = 'Disabled';
$string['settingscompletion1'] = 'Manual';
$string['settingscompletion2'] = 'Automatic';
$string['settingscompletionview'] = 'Require viewing';
$string['settingscompletiongrade'] = 'Require a grade';
$string['settingscompletionexpected'] = 'Expected completion date';
$string['settingsrequiredentries'] = 'Required entries (legacy setting)';
$string['settingsrequiredentriestoview'] = 'Entries required before viewing';
$string['settingsmaxentries'] = 'Maximum entries per participant';
$string['settingscompletionentries'] = 'Entries required for completion';
$string['settingsrssarticles'] = 'Entries in RSS feed';
$string['settingstimecreated'] = 'Creation date';
$string['settingstimemodified'] = 'Modification date';
$string['settingsapprovalstatus'] = 'Approval status';
$string['settingssort'] = 'Default sort field';
$string['settingssortdirection'] = 'Sort direction';
$string['settingsascending'] = 'Ascending';
$string['settingsdescending'] = 'Descending';
$string['settingsrating'] = 'Rating aggregation';
$string['settingsrating0'] = 'No ratings';
$string['settingsrating1'] = 'Average';
$string['settingsrating2'] = 'Count';
$string['settingsrating3'] = 'Maximum';
$string['settingsrating4'] = 'Minimum';
$string['settingsrating5'] = 'Sum';
$string['settingsscale'] = 'Rating scale or maximum grade';
$string['settingsratingfrom'] = 'Ratings from';
$string['settingsratingto'] = 'Ratings until';
$string['settingsgradepass'] = 'Grade to pass';
$string['settingsgradecategory'] = 'Grade category';
$string['settingsfields'] = 'Field definitions';
$string['settingsfieldtype'] = 'Field type';
$string['settingsfieldrequired'] = 'Required field';
$string['settingsfieldoptions'] = 'Available choices';
$string['settingsautolink'] = 'Automatic linking';
$string['settingscolumns'] = 'Editor width (columns)';
$string['settingsrows'] = 'Editor height (rows)';
$string['settingsmaxbytes'] = 'Maximum file size (bytes; 0 = inherited)';
$string['settingswidth'] = 'Image width (pixels)';
$string['settingsheight'] = 'Image height (pixels)';
$string['settingsthumbwidth'] = 'Thumbnail width (pixels)';
$string['settingsthumbheight'] = 'Thumbnail height (pixels)';
$string['settingslinktext'] = 'Link label';
$string['settingsnewwindow'] = 'Open in a new window';
$string['settingstemplates'] = 'Presentation templates (source)';
$string['settingslisttemplateheader'] = 'List header';
$string['settingslisttemplate'] = 'List entry';
$string['settingslisttemplatefooter'] = 'List footer';
$string['settingssingletemplate'] = 'Single entry';
$string['settingsaddtemplate'] = 'Add entry form';
$string['settingsasearchtemplate'] = 'Advanced search';
$string['settingsrsstemplate'] = 'RSS entry';
$string['settingsrsstitletemplate'] = 'RSS title';
$string['settingscsstemplate'] = 'Custom CSS';
$string['settingsjavascript'] = 'Custom JavaScript present';

// Course and activity settings snapshots.
$string['settingsgeneralscope'] = 'Snapshot of standard course or activity settings. Passwords, secrets, user overrides and third-party plugin configuration are not included.';
$string['settingsbackactivity'] = 'Back to activity';
$string['settingspasswordset'] = 'Password configured (value omitted)';
$string['settingscourselang'] = 'Forced language';
$string['settingscoursenews'] = 'Number of announcements';
$string['settingscoursegrades'] = 'Show gradebook to students';
$string['settingscoursereports'] = 'Show activity reports';
$string['settingscoursedates'] = 'Show activity dates';
$string['settingscoursecompletion'] = 'Show completion conditions';
$string['settingsreviewduring'] = 'During the attempt';
$string['settingsreviewimmediately'] = 'Immediately after the attempt';
$string['settingsreviewopen'] = 'Later, while the quiz is still open';
$string['settingsreviewclosed'] = 'After the quiz is closed';
$string['settingsreviewattempt'] = 'The attempt';
$string['settingsreviewcorrectness'] = 'Whether correct';
$string['settingsreviewmarks'] = 'Marks';
$string['settingsreviewspecificfeedback'] = 'Specific feedback';
$string['settingsreviewgeneralfeedback'] = 'General feedback';
$string['settingsreviewrightanswer'] = 'Right answer';
$string['settingsreviewoverallfeedback'] = 'Overall feedback';
$string['settingfield_display'] = 'Display mode';
$string['settingfield_multiple_submit'] = 'Allow multiple submissions';
$string['settingfield_publish_stats'] = 'Publish analysis';
$string['settingfield_timeopen'] = 'Opening date';
$string['settingfield_timeclose'] = 'Closing date';
$string['settingfield_showresults'] = 'When results are shown';
$string['settingfield_maxbytes'] = 'Maximum attachment size';
$string['settingfield_grade'] = 'Maximum grade';
$string['settingfield_usemaxgrade'] = 'Use maximum grade across attempts';
$string['settingfield_maxattempts'] = 'Maximum attempts per question';
$string['settingfield_feedback'] = 'Show default feedback';
$string['settingfield_minquestions'] = 'Minimum number of questions';
$string['settingfield_maxpages'] = 'Maximum number of pages';
$string['settingfield_displayleft'] = 'Display menu';
$string['settingfield_attemptonlast'] = 'Each attempt builds on the last';
$string['settingfield_questiondecimalpoints'] = 'Decimal places in question grades';
$string['settingfield_allowofflineattempts'] = 'Allow offline attempts';
$string['settingsdisplayauto'] = 'Automatic';
$string['settingsdisplayembed'] = 'Embed';
$string['settingsdisplayframe'] = 'Frame';
$string['settingsdisplaynew'] = 'New window';
$string['settingsdisplaydownload'] = 'Force download';
$string['settingsdisplayopen'] = 'Open';
$string['settingsdisplaypopup'] = 'Pop-up';
$string['settingsprintintro'] = 'Display description';
$string['settingsprintheading'] = 'Display heading';
$string['settingsshowsize'] = 'Display file size';
$string['settingsshowtype'] = 'Display file type';
$string['settingsshowdate'] = 'Display file modification date';
$string['settingspopupwidth'] = 'Pop-up width (pixels)';
$string['settingspopupheight'] = 'Pop-up height (pixels)';
$string['settingfield_completiontimespent'] = 'Minimum time spent for completion';
$string['settingsnavtoc'] = 'Table of contents only (legacy navigation)';
$string['settingsnavimages'] = 'Images (legacy navigation)';
$string['settingsnavtext'] = 'Text (legacy navigation)';
$string['settingsemptyfield'] = 'Empty field';

$string['urlexternalnotice'] = 'External destination: an Internet connection may be required. The destination website is not included in this archive.';
$string['urlparametersnotice'] = 'Dynamic Moodle parameters are not added to this archived link.';
$string['assignarchivescope'] = 'Static snapshot: online text, submitted files, assignment grades and feedback comments/files. Advanced grading forms and PDF annotations are not reproduced.';
$string['assignanonymous'] = 'Participant {$a}';
$string['assignrecords'] = 'Recorded submissions and grades';
$string['assignnorecords'] = 'No submission or grade is available for this export with your permissions.';
$string['assignattempt'] = 'Attempt {$a}';
$string['assigngroupsubmission'] = 'Group submission';
$string['assignnotgraded'] = 'Not graded';
$string['assignunsupported'] = 'Content not exported for this extension: {$a}';
$string['assigngrade'] = 'Assignment grade';

// Native H5P static export.
$string['h5pinvalid'] = 'The H5P package is missing, invalid or exceeds the archive limits.';
$string['h5pstaticnotice'] = 'Static reading copy: exercises cannot be answered here. Solutions and feedback are preserved. The ' .
    'original H5P package is included separately; unsupported elements are identified.';
$string['h5psource'] = 'Download original H5P package';
$string['h5punsupported'] = 'Element not converted to static HTML: {$a}. Consult the original H5P package.';
$string['h5pmediaunavailable'] = 'Media not embedded: {$a}. External media requires internet; other files remain in the original package.';
$string['h5pchoices'] = 'Possible answers';
$string['h5psolutions'] = 'Solutions';
$string['h5pdistractors'] = 'Additional distractor words';
$string['h5pwords'] = 'Words / accepted alternatives';
$string['h5ptrue'] = 'True';
$string['h5pfalse'] = 'False';
$string['h5pelements'] = 'Elements to place';
$string['h5pzone'] = 'Zone {$a}';
$string['h5pslide'] = 'Slide {$a}';
$string['h5pquestion'] = 'Question {$a}';
$string['h5pslidelink'] = 'Go to slide: {$a}';
$string['h5pjumpcorrect'] = 'Correct answer: jump to {$a}';
$string['h5pjumpwrong'] = 'Incorrect answer: jump to {$a}';
$string['h5pjump'] = 'Jump to {$a}';
$string['h5prequirecompletion'] = 'This interaction originally required completion before continuing.';
$string['h5ptip'] = 'Hint';
$string['h5pchosenfeedback'] = 'Feedback when selected';
$string['h5pnotchosenfeedback'] = 'Feedback when not selected';
$string['h5pfeedbackoncorrect'] = 'Feedback for a correct answer';
$string['h5pfeedbackonincorrect'] = 'Feedback for an incorrect answer';
$string['h5pfeedbackonwrong'] = 'Feedback for an incorrect answer';
$string['h5pcredits'] = 'Attribution and licence';
$string['h5pback'] = 'Back to H5P activity';
$string['h5pcontentsettings'] = 'H5P authoring settings (stored values)';
$string['h5pattempts'] = 'Recorded attempts';
$string['h5pattemptnotice'] = 'Only recorded Moodle reports accessible to the exporter are included. Historical answers are not ' .
    'recalculated against the current content.';
$string['h5pnoreports'] = 'No accessible recorded report.';
$string['h5pattempt'] = 'Attempt {$a}';
$string['h5prawresponse'] = 'Recorded response (original xAPI encoding)';
$string['h5pscore'] = 'Recorded score';
$string['h5pcompletion'] = 'Completed';
$string['h5psuccess'] = 'Passed';
$string['h5pyes'] = 'Yes';
$string['h5pno'] = 'No';
$string['h5punknown'] = 'Not recorded';
$string['h5pduration'] = 'Recorded duration: {$a} seconds';
$string['settingfield_h5ptracking'] = 'Attempt tracking';
$string['settingfield_h5pgrademethod'] = 'Grading method (Moodle value)';
$string['settingfield_h5previewmode'] = 'Review mode (Moodle value)';
$string['settingfield_h5pdisplayoptions'] = 'Display options (Moodle bitmask)';
$string['h5punresolvedlink'] = 'Unresolved link: {$a}';

// Workshop archive.
$string['wsscope'] = 'Static Workshop snapshot. Recorded grades are preserved without recalculation. ' .
    'Names and reports follow Moodle permissions. Original files may identify their ' .
    'authors.';
$string['wsinstructauthors'] = 'Submission instructions';
$string['wsinstructreviewers'] = 'Assessment instructions';
$string['wsconclusion'] = 'Conclusion';
$string['wsform'] = 'Assessment form';
$string['wssubmissions'] = 'Submissions';
$string['wsexample'] = 'Example submission';
$string['wsback'] = 'Back to Workshop';
$string['wsauthor'] = 'Author';
$string['wsreviewer'] = 'Reviewer';
$string['wscreated'] = 'Created';
$string['wsmodified'] = 'Last modified';
$string['wslate'] = 'Late submission';
$string['wspublished'] = 'Published submission';
$string['wssubmissiongrade'] = 'Submission grade';
$string['wsassessmentgrade'] = 'Assessment quality grade';
$string['wsoverride'] = 'Teacher override';
$string['wsfeedbackauthor'] = 'Feedback for the author';
$string['wsfeedbackreviewer'] = 'Teacher feedback for the reviewer';
$string['wsassessment'] = 'Assessment {$a}';
$string['wsassessments'] = 'Assessments';
$string['wsrestricted'] = 'Reports omitted: required Moodle permissions are missing.';
$string['wsnosubmissions'] = 'No accessible submission.';
$string['wsweight'] = 'Weight';
$string['wspending'] = 'Allocated, not yet assessed';
$string['wsunsupported'] = 'Assessment strategy not converted';
$string['wsstrategy'] = 'Assessment strategy';
$string['wscriterion'] = 'Criterion {$a}';
$string['wsmatchedlevel'] = 'Matches recorded score';
$string['wsrecorded'] = 'Recorded answer';
$string['wsmaximum'] = 'Maximum points';
$string['wsrecordedvalue'] = 'Recorded numeric value';
$string['wsnotgraded'] = 'Not graded';
$string['wscomment'] = 'Comment';
$string['wsnoanswer'] = 'No recorded answer for this criterion.';
$string['wserrormap'] = 'Errors-to-grade mapping';
$string['wsaggregate'] = 'Aggregated assessment quality grades';
$string['wsanonymous'] = 'Anonymous participant';
$string['settingfield_wsphase'] = 'Phase (Moodle value)';
$string['settingfield_wsstrategy'] = 'Strategy';
$string['settingfield_wsgrade'] = 'Maximum submission grade';
$string['settingfield_wsgradinggrade'] = 'Maximum assessment grade';
$string['settingfield_wsgradedecimals'] = 'Decimal places';
$string['settingfield_wsuseexamples'] = 'Example submissions';
$string['settingfield_wsuseselfassessment'] = 'Self-assessment';
$string['settingfield_wssubmissionstart'] = 'Submission opens';
$string['settingfield_wssubmissionend'] = 'Submission deadline';
$string['settingfield_wsassessmentstart'] = 'Assessment opens';
$string['settingfield_wsassessmentend'] = 'Assessment deadline';
$string['settingfield_wsphaseswitchassessment'] = 'Automatic phase switch';
$string['settingfield_wslatesubmissions'] = 'Late submissions allowed';
$string['settingfield_wssubmissiontypetext'] = 'Online text mode (Moodle value)';
$string['settingfield_wssubmissiontypefile'] = 'File submission mode (Moodle value)';
$string['settingfield_wsnattachments'] = 'Maximum attachments';
$string['settingfield_wssubmissionfiletypes'] = 'Submission file types';
$string['settingfield_wsmaxbytes'] = 'Maximum file size';
$string['settingfield_wsexamplesmode'] = 'Example assessment mode (Moodle value)';
$string['settingfield_wsoverallfeedbackmode'] = 'Overall feedback mode (Moodle value)';
$string['settingfield_wsoverallfeedbackfiles'] = 'Feedback attachments';
$string['settingfield_wsoverallfeedbackfiletypes'] = 'Feedback file types';
$string['settingfield_wsoverallfeedbackmaxbytes'] = 'Maximum feedback file size';
