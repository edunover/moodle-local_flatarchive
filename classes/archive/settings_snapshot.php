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

namespace local_flatarchive\archive;

/**
 * Read-only, allowlisted course and activity settings snapshots.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class settings_snapshot {
    /**
     * Add settings to successfully exported activities and register every generated page.
     *
     * @param array $items Course index entries
     * @param \course_modinfo $modinfo Course information
     * @param string $directory Archive root
     * @return array Updated items, files and optional course settings link
     */
    public static function add(array $items, \course_modinfo $modinfo, string $directory): array {
        global $CFG;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        $files = [];
        foreach ($items as $cmid => $item) {
            $cm = $modinfo->get_cm($cmid);
            if ($cm->modname === 'data' || !has_capability('moodle/course:manageactivities', $cm->context)) {
                continue;
            }
            if (!array_key_exists($cm->modname, self::definitions()) || !$cm->uservisible) {
                continue;
            }
            // Only the exporter-owned main document may be amended. Never rewrite attached HTML files.
            $target = '';
            if (!in_array($cm->modname, ['label', 'resource'], true)) {
                if (preg_match('/<a\b[^>]*href="([^"]+)"/i', $item, $matches)) {
                    $candidate = rawurldecode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
                    $real = realpath($directory . '/' . $candidate);
                    $root = realpath($directory) . DIRECTORY_SEPARATOR;
                    if ($real && str_starts_with($real, $root) && str_ends_with($candidate, '.html')) {
                        $target = $candidate;
                    }
                }
            }
            $back = $target ?: 'index.html';
            $filename = self::activity($cm, $modinfo, $directory, $back);
            $files[$filename] = $directory . '/' . $filename;
            $link = \html_writer::link($filename, self::label('archivesettings'), ['class' => 'fa-settings-link']);
            $items[$cmid] = preg_replace('/<\/li>\s*$/', ' ' . $link . '</li>', $item);
            if ($target) {
                $prefix = str_repeat('../', substr_count($target, '/'));
                $link = \html_writer::tag('p', \html_writer::link($prefix . $filename, self::label('archivesettings')));
                $html = file_get_contents($directory . '/' . $target);
                $html = preg_replace_callback('/<h1\b[^>]*>.*?<\/h1>/s', fn($match) => $match[0] . $link, $html, 1);
                \local_flatarchive_write_file($directory . '/' . $target, $html);
            }
        }
        $courselink = '';
        $course = $modinfo->get_course();
        if (has_capability('moodle/course:update', \context_course::instance($course->id))) {
            $filename = self::course($course, $directory);
            $files[$filename] = $directory . '/' . $filename;
            $courselink = \html_writer::tag('p', \html_writer::link($filename, self::label('archivesettings')));
        }
        return ['items' => $items, 'files' => $files, 'courselink' => $courselink];
    }

    /**
     * Write a module snapshot; no arbitrary configuration is serialised.
     *
     * @param \cm_info $cm Module
     * @param \course_modinfo $modinfo Course information
     * @param string $directory Archive root
     * @param string $back Local activity document
     * @return string Generated filename
     */
    public static function activity(\cm_info $cm, \course_modinfo $modinfo, string $directory, string $back): string {
        global $DB;
        require_capability('moodle/course:manageactivities', $cm->context);
        $definitions = self::definitions();
        if (!array_key_exists($cm->modname, $definitions)) {
            throw new \coding_exception('Unsupported settings snapshot module');
        }
        $instance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
        $module = $DB->get_record('course_modules', ['id' => $cm->id], '*', MUST_EXIST);
        $rows = [get_string('name') => $cm->name];
        $rows[self::label('settingsvisibility')] = !$cm->visible ? self::label('settingshidden') :
            self::label($cm->is_stealth() ? 'settingsstealth' : 'settingsvisible');
        $rows[self::label('settingsshowdescription')] = self::value($module->showdescription, 'bool', '');
        $groupmode = groups_get_activity_groupmode($cm);
        $rows[get_string('groupmode')] = get_string(['groupsnone', 'groupsseparate', 'groupsvisible'][$groupmode]);
        $rows[self::label('settingsgroupforced')] = self::value($modinfo->get_course()->groupmodeforce, 'bool', '');
        $rows[get_string('grouping', 'group')] = $module->groupingid ?
            (string)$DB->get_field('groupings', 'name', ['id' => $module->groupingid]) : get_string('none');
        $availability = new \core_availability\info_module($cm);
        $rows[self::label('settingsaccess')] = trim(strip_tags($availability->get_full_information())) ?: get_string('none');
        $rows[self::label('settingscompletion')] = self::label('settingscompletion' . (int)$module->completion);
        $rows[self::label('settingscompletionview')] = self::value($module->completionview, 'bool', '');
        $rows[self::label('settingscompletiongrade')] = self::value($module->completiongradeitemnumber !== null, 'bool', '');
        $rows[self::label('settingscompletionexpected')] = self::value($module->completionexpected, 'date', '');
        foreach ($definitions[$cm->modname] as $field => [$type, $label]) {
            if (property_exists($instance, $field)) {
                $component = str_starts_with($label, 'settingfield_') ? 'local_flatarchive' : $cm->modname;
                $rows[get_string($label, $component)] = self::value($instance->$field, $type, $cm->modname);
            }
        }
        if (in_array($cm->modname, ['page', 'resource'], true) && !empty($instance->displayoptions)) {
            // Moodle stores these display switches as a serialized array, never as executable objects.
            $options = unserialize($instance->displayoptions, ['allowed_classes' => false]);
            if (is_array($options)) {
                foreach (['printintro', 'printheading', 'showsize', 'showtype', 'showdate'] as $key) {
                    if (array_key_exists($key, $options)) {
                        $rows[self::label('settings' . $key)] = self::value($options[$key], 'bool', '');
                    }
                }
                foreach (['popupwidth', 'popupheight'] as $key) {
                    if (isset($options[$key]) && is_numeric($options[$key])) {
                        $rows[self::label('settings' . $key)] = (string)(int)$options[$key];
                    }
                }
            }
        }
        if (property_exists($instance, 'password')) {
            $rows[self::label('settingspasswordset')] = self::value($instance->password !== '', 'bool', '');
        }
        if (property_exists($instance, 'assessed')) {
            $rows[self::label('settingsrating')] = self::label('settingsrating' . (int)$instance->assessed);
            $rows[self::label('settingsscale')] = $instance->scale < 0 ?
                (string)$DB->get_field('scale', 'name', ['id' => -$instance->scale]) : (string)$instance->scale;
            $rows[self::label('settingsratingfrom')] = self::value($instance->assesstimestart, 'date', '');
            $rows[self::label('settingsratingto')] = self::value($instance->assesstimefinish, 'date', '');
        }
        $body = self::intro() . self::table($rows);
        $grades = $DB->get_records('grade_items', [
            'itemtype' => 'mod', 'itemmodule' => $cm->modname, 'iteminstance' => $cm->instance,
        ], 'itemnumber');
        foreach ($grades as $grade) {
            $category = $DB->get_field('grade_categories', 'fullname', ['id' => $grade->categoryid]);
            $body .= self::table([
                get_string('name') => $grade->itemname ?: $cm->name,
                self::label('settingsgradepass') => format_float($grade->gradepass),
                self::label('settingsgradecategory') => $category === '?' ? $modinfo->get_course()->fullname : $category,
            ]);
        }
        if ($cm->modname === 'quiz') {
            $body .= self::quiz_review($instance);
        }
        $body .= \html_writer::tag('p', \html_writer::link(self::url($back), self::label('settingsbackactivity')));
        $filename = $cm->modname . '-' . $cm->id . '-settings.html';
        \local_flatarchive_write_file($directory . '/' . $filename, \local_flatarchive_render_page_template(
            $modinfo->get_course(),
            $cm,
            $cm->name . ' — ' . self::label('archivesettings'),
            $body
        ));
        return $filename;
    }

    /**
     * Write the course's standard settings, without enrolment secrets or user data.
     *
     * @param \stdClass $course Course
     * @param string $directory Archive root
     * @return string Generated filename
     */
    public static function course(\stdClass $course, string $directory): string {
        global $DB;
        require_capability('moodle/course:update', \context_course::instance($course->id));
        $rows = [get_string('fullnamecourse') => $course->fullname, get_string('shortnamecourse') => $course->shortname];
        $rows[get_string('category')] = $DB->get_field('course_categories', 'name', ['id' => $course->category]);
        $component = 'format_' . $course->format;
        $rows[get_string('format')] = get_string_manager()->string_exists('pluginname', $component) ?
            get_string('pluginname', $component) : $course->format;
        $rows[self::label('settingsvisibility')] = self::label($course->visible ? 'settingsvisible' : 'settingshidden');
        foreach (['startdate', 'enddate'] as $field) {
            $rows[get_string($field)] = self::value($course->$field, 'date', '');
        }
        $rows[get_string('groupmode')] = get_string(['groupsnone', 'groupsseparate', 'groupsvisible'][$course->groupmode]);
        $rows[self::label('settingsgroupforced')] = self::value($course->groupmodeforce, 'bool', '');
        $rows[get_string('grouping', 'group')] = $course->defaultgroupingid ?
            (string)$DB->get_field('groupings', 'name', ['id' => $course->defaultgroupingid]) : get_string('none');
        $rows[self::label('settingscompletion')] = self::value($course->enablecompletion, 'bool', '');
        $rows[self::label('settingsmaxbytes')] = self::value($course->maxbytes, 'bytes', '');
        $rows[self::label('settingscourselang')] = $course->lang ?: get_string('default');
        $rows[self::label('settingscoursenews')] = (string)$course->newsitems;
        $rows[self::label('settingscoursegrades')] = self::value($course->showgrades, 'bool', '');
        $rows[self::label('settingscoursereports')] = self::value($course->showreports, 'bool', '');
        $rows[self::label('settingscoursedates')] = self::value($course->showactivitydates ?? 0, 'bool', '');
        $rows[self::label('settingscoursecompletion')] = self::value($course->showcompletionconditions ?? 0, 'bool', '');
        $body = self::intro() . self::table($rows);
        $filename = 'course-settings.html';
        \local_flatarchive_write_file($directory . '/' . $filename, \local_flatarchive_render_page_template(
            $course,
            null,
            $course->fullname . ' — ' . self::label('archivesettings'),
            $body
        ));
        return $filename;
    }

    /**
     * Decode Quiz review bitmasks into the four native review periods.
     * @param \stdClass $quiz Quiz settings
     * @return string HTML
     */
    private static function quiz_review(\stdClass $quiz): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $periods = [
            'during' => \mod_quiz\question\display_options::DURING,
            'immediately' => \mod_quiz\question\display_options::IMMEDIATELY_AFTER,
            'open' => \mod_quiz\question\display_options::LATER_WHILE_OPEN,
            'closed' => \mod_quiz\question\display_options::AFTER_CLOSE,
        ];
        $fields = ['attempt', 'correctness', 'marks', 'specificfeedback', 'generalfeedback', 'rightanswer', 'overallfeedback'];
        $body = \html_writer::tag('h2', get_string('reviewoptionsheading', 'quiz'));
        foreach ($periods as $period => $mask) {
            $rows = [];
            foreach ($fields as $field) {
                $key = 'review' . $field;
                if (property_exists($quiz, $key)) {
                    $rows[self::label('settingsreview' . $field)] = self::value(((int)$quiz->$key & $mask) !== 0, 'bool', '');
                }
            }
            $body .= \html_writer::tag('h3', self::label('settingsreview' . $period)) . self::table($rows);
        }
        return $body;
    }

    /**
     * Format an explicitly typed stored setting.
     * @param mixed $value Stored value
     * @param string $type Display type
     * @param string $module Language component
     * @return string Plain text
     */
    private static function value($value, string $type, string $module): string {
        if ($type === 'bool') {
            return get_string($value ? 'yes' : 'no');
        }
        if ($type === 'date') {
            return $value ? userdate((int)$value) : self::label('settingscompletion0');
        }
        if ($type === 'duration') {
            return $value ? format_time((int)$value) : self::label('settingscompletion0');
        }
        if ($type === 'bytes') {
            return $value ? display_size((int)$value) : get_string('default');
        }
        if ($type === 'behaviour') {
            $component = 'qbehaviour_' . $value;
            return get_string_manager()->string_exists('pluginname', $component) ? get_string('pluginname', $component) : $value;
        }
        $maps = [
            'booknumbering' => ['numbering0', 'numbering1', 'numbering2', 'numbering3'],
            'booknav' => ['navtoc', 'navimages', 'navtext'],
            'folderdisplay' => ['displaypage', 'displayinline'],
            'choicedisplay' => ['displayhorizontal', 'displayvertical'],
            'publish' => ['publishanonymous', 'publishnames'],
            'results' => ['publishnot', 'publishafteranswer', 'publishafterclose', 'publishalways'],
            'anonymous' => [1 => 'anonymous', 2 => 'non_anonymous'],
            'subscribe' => ['subscriptionoptional', 'subscriptionforced', 'subscriptionauto', 'subscriptiondisabled'],
            'tracking' => ['trackingoff', 'trackingoptional', 'trackingon'],
            'grademethod' => [1 => 'gradehighest', 2 => 'gradeaverage', 3 => 'attemptfirst', 4 => 'attemptlast'],
            'navigation' => ['free' => 'navmethod_free', 'sequential' => 'navmethod_seq'],
            'overdue' => ['autosubmit' => 'overduehandlingautosubmit', 'graceperiod' => 'overduehandlinggraceperiod',
                'autoabandon' => 'overduehandlingautoabandon'],
            'wiki' => ['collaborative' => 'wikimodecollaborative', 'individual' => 'wikimodeindividual'],
            'forumtype' => ['general' => 'generalforum', 'eachuser' => 'eachuserforum', 'single' => 'singleforum',
                'qanda' => 'qandaforum', 'blog' => 'blogforum', 'news' => 'namenews', 'social' => 'namesocial'],
        ];
        if ($type === 'display') {
            $maps['display'] = [0 => 'displayauto', 1 => 'displayembed', 2 => 'displayframe', 3 => 'displaynew',
                4 => 'displaydownload', 5 => 'displayopen', 6 => 'displaypopup'];
            $module = 'local_flatarchive';
            $maps['display'] = array_map(fn($key) => 'settings' . $key, $maps['display']);
        }
        if ($type === 'booknav') {
            $module = 'local_flatarchive';
            $maps['booknav'] = ['settingsnavtoc', 'settingsnavimages', 'settingsnavtext'];
        }
        $key = $maps[$type][$value] ?? null;
        if ($key && get_string_manager()->string_exists($key, $module)) {
            return get_string($key, $module);
        }
        return (string)$value;
    }

    /**
     * Render escaped configuration, never executable settings or custom markup.
     * @param array $rows Labels and values
     * @return string HTML table
     */
    private static function table(array $rows): string {
        $body = '';
        foreach ($rows as $label => $value) {
            $body .= \html_writer::tag('tr', \html_writer::tag('th', s($label), ['scope' => 'row']) .
                \html_writer::tag('td', nl2br(s((string)$value))));
        }
        return \html_writer::tag('table', \html_writer::tag('tbody', $body), ['class' => 'fa-settings-table']);
    }

    /**
     * State snapshot date and scope.
     * @return string HTML
     */
    private static function intro(): string {
        return \html_writer::tag('p', self::label('settingssnapshot') . ' ' . s(userdate(time()))) .
            \html_writer::tag('p', self::label('settingsgeneralscope'));
    }

    /**
     * Translate a plugin label.
     * @param string $key Identifier
     * @return string Translation
     */
    private static function label(string $key): string {
        return get_string($key, 'local_flatarchive');
    }

    /**
     * Encode a local relative filename, preserving directory separators.
     * @param string $path Local relative path
     * @return string URL
     */
    private static function url(string $path): string {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    /**
     * Allowlist of supported settings and their presentation types.
     * @return array Module, field, type and language string
     */
    private static function definitions(): array {
        return [
            'page' => [
                'display' => ['display', 'settingfield_display'],
            ],
            'book' => [
                'numbering' => ['booknumbering', 'numbering'],
                'navstyle' => ['booknav', 'navstyle'],
                'customtitles' => ['bool', 'customtitles'],
            ],
            'label' => [
            ],
            'resource' => [
                'display' => ['display', 'settingfield_display'],
                'filterfiles' => ['bool', 'filterfiles'],
            ],
            'folder' => [
                'display' => ['folderdisplay', 'display'],
                'showexpanded' => ['bool', 'showexpanded'],
                'showdownloadfolder' => ['bool', 'showdownloadfolder'],
                'forcedownload' => ['bool', 'forcedownload'],
            ],
            'glossary' => [
                'allowduplicatedentries' => ['bool', 'allowduplicatedentries'],
                'displayformat' => ['text', 'displayformat'],
                'mainglossary' => ['bool', 'glossarytype'],
                'showspecial' => ['bool', 'showspecial'],
                'showalphabet' => ['bool', 'showalphabet'],
                'showall' => ['bool', 'showall'],
                'allowcomments' => ['bool', 'allowcomments'],
                'allowprintview' => ['bool', 'allowprintview'],
                'usedynalink' => ['bool', 'usedynalink'],
                'defaultapproval' => ['bool', 'defaultapproval'],
                'entbypage' => ['number', 'entbypage'],
                'editalways' => ['bool', 'editalways'],
                'completionentries' => ['number', 'completionentries'],
            ],
            'wiki' => [
                'firstpagetitle' => ['text', 'firstpagetitle'],
                'wikimode' => ['wiki', 'wikimode'],
                'defaultformat' => ['text', 'defaultformat'],
                'forceformat' => ['bool', 'forceformat'],
            ],
            'feedback' => [
                'anonymous' => ['anonymous', 'anonymous'],
                'email_notification' => ['bool', 'email_notification'],
                'multiple_submit' => ['bool', 'settingfield_multiple_submit'],
                'autonumbering' => ['bool', 'autonumbering'],
                'publish_stats' => ['bool', 'settingfield_publish_stats'],
                'timeopen' => ['date', 'settingfield_timeopen'],
                'timeclose' => ['date', 'settingfield_timeclose'],
                'completionsubmit' => ['bool', 'completionsubmit'],
            ],
            'choice' => [
                'publish' => ['publish', 'publish'],
                'showresults' => ['results', 'settingfield_showresults'],
                'display' => ['choicedisplay', 'settingfield_display'],
                'allowupdate' => ['bool', 'allowupdate'],
                'allowmultiple' => ['bool', 'allowmultiple'],
                'showunanswered' => ['bool', 'showunanswered'],
                'includeinactive' => ['bool', 'includeinactive'],
                'limitanswers' => ['bool', 'limitanswers'],
                'timeopen' => ['date', 'settingfield_timeopen'],
                'timeclose' => ['date', 'settingfield_timeclose'],
                'showpreview' => ['bool', 'showpreview'],
                'completionsubmit' => ['bool', 'completionsubmit'],
                'showavailable' => ['bool', 'showavailable'],
            ],
            'forum' => [
                'type' => ['forumtype', 'forumtype'],
                'duedate' => ['date', 'duedate'],
                'cutoffdate' => ['date', 'cutoffdate'],
                'maxbytes' => ['bytes', 'settingfield_maxbytes'],
                'maxattachments' => ['number', 'maxattachments'],
                'forcesubscribe' => ['subscribe', 'subscriptionmode'],
                'trackingtype' => ['tracking', 'trackingtype'],
                'warnafter' => ['number', 'warnafter'],
                'blockafter' => ['number', 'blockafter'],
                'blockperiod' => ['duration', 'blockperiod'],
                'completiondiscussions' => ['number', 'completiondiscussions'],
                'completionreplies' => ['number', 'completionreplies'],
                'completionposts' => ['number', 'completionposts'],
                'displaywordcount' => ['bool', 'displaywordcount'],
                'lockdiscussionafter' => ['duration', 'lockdiscussionafter'],
            ],
            'lesson' => [
                'practice' => ['bool', 'practice'],
                'modattempts' => ['bool', 'modattempts'],
                'usepassword' => ['bool', 'usepassword'],
                'grade' => ['number', 'settingfield_grade'],
                'custom' => ['bool', 'customscoring'],
                'ongoing' => ['bool', 'ongoing'],
                'usemaxgrade' => ['bool', 'settingfield_usemaxgrade'],
                'maxanswers' => ['number', 'maximumnumberofanswersbranches'],
                'maxattempts' => ['number', 'settingfield_maxattempts'],
                'review' => ['bool', 'review'],
                'feedback' => ['bool', 'settingfield_feedback'],
                'minquestions' => ['number', 'settingfield_minquestions'],
                'maxpages' => ['number', 'settingfield_maxpages'],
                'timelimit' => ['duration', 'timelimit'],
                'retake' => ['bool', 'retakesallowed'],
                'displayleft' => ['bool', 'settingfield_displayleft'],
                'displayleftif' => ['number', 'displayleftif'],
                'progressbar' => ['bool', 'progressbar'],
                'available' => ['date', 'available'],
                'deadline' => ['date', 'deadline'],
                'completionendreached' => ['bool', 'completionendreached'],
                'completiontimespent' => ['duration', 'settingfield_completiontimespent'],
                'allowofflineattempts' => ['bool', 'allowofflineattempts'],
            ],
            'quiz' => [
                'timeopen' => ['date', 'settingfield_timeopen'],
                'timeclose' => ['date', 'settingfield_timeclose'],
                'timelimit' => ['duration', 'timelimit'],
                'overduehandling' => ['overdue', 'overduehandling'],
                'graceperiod' => ['duration', 'graceperiod'],
                'preferredbehaviour' => ['behaviour', 'questionbehaviour'],
                'canredoquestions' => ['bool', 'canredoquestions'],
                'attempts' => ['number', 'attemptsallowed'],
                'attemptonlast' => ['bool', 'settingfield_attemptonlast'],
                'grademethod' => ['grademethod', 'grademethod'],
                'decimalpoints' => ['number', 'decimalpoints'],
                'questiondecimalpoints' => ['number', 'settingfield_questiondecimalpoints'],
                'questionsperpage' => ['number', 'questionsperpage'],
                'navmethod' => ['navigation', 'navmethod'],
                'shuffleanswers' => ['bool', 'shuffleanswers'],
                'grade' => ['number', 'settingfield_grade'],
                'delay1' => ['duration', 'delay1'],
                'delay2' => ['duration', 'delay2'],
                'showblocks' => ['bool', 'showblocks'],
                'completionattemptsexhausted' => ['bool', 'completionattemptsexhausted'],
                'completionminattempts' => ['number', 'completionminattempts'],
                'allowofflineattempts' => ['bool', 'settingfield_allowofflineattempts'],
            ],
        ];
    }
}
