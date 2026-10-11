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
 * Preserve the authored lesson graph without starting attempts or repairing page order.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lesson_exporter {
    /** @var array Core page type identifiers and language keys. */
    private const TYPES = [1 => 'shortanswer', 2 => 'truefalse', 3 => 'multichoice', 5 => 'matching',
        8 => 'numerical', 10 => 'essay', 20 => 'branchtable', 21 => 'endofbranch', 30 => 'cluster', 31 => 'endofcluster'];

    /**
     * Export the complete authored content, including solutions, for lesson editors only.
     *
     * @param \cm_info $cm Module.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Destination.
     * @return array Index item and generated HTML filenames.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'lesson') {
            throw new \coding_exception('The lesson exporter requires a lesson');
        }
        $context = \context_module::instance($cm->id);
        require_capability('mod/lesson:view', $context);
        // Viewing a lesson does not grant access to every branch, solution or feedback.
        require_capability('mod/lesson:edit', $context);
        if (!$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $lesson = $DB->get_record('lesson', ['id' => $cm->instance], '*', MUST_EXIST);
        $pages = self::ordered_pages($DB->get_records('lesson_pages', ['lessonid' => $lesson->id], 'id'));
        $answers = [];
        foreach ($DB->get_records('lesson_answers', ['lessonid' => $lesson->id], 'id') as $answer) {
            $answers[$answer->pageid][] = $answer;
        }
        $filename = 'lesson-' . $cm->id . '.html';
        $files = [$filename];
        $map = [];
        foreach ($pages as $page) {
            $map[$page->id] = 'lesson-' . $cm->id . '-page-' . $page->id . '.html';
        }
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        $content = self::text($lesson->intro, $lesson->introformat, 'intro', 0, $cm, $directory);
        $content .= \html_writer::tag('p', get_string('lessonscope', 'local_flatarchive'));
        $items = [];
        foreach ($pages as $page) {
            $type = isset(self::TYPES[$page->qtype]) ? get_string(self::TYPES[$page->qtype], 'lesson') :
                get_string('lessonunknown', 'local_flatarchive', $page->qtype);
            $items[] = \html_writer::link($map[$page->id], s($page->title)) . ' — ' . s($type);
            $body = \html_writer::tag('p', \html_writer::link($filename, get_string('backtolesson', 'local_flatarchive')));
            $body .= \html_writer::tag('p', s($type));
            $body .= self::text($page->contents, $page->contentsformat, 'page_contents', $page->id, $cm, $directory);
            if ((int)$page->qtype === 3 && $page->qoption) {
                $body .= \html_writer::tag('p', get_string('lessonmultiple', 'local_flatarchive'));
            }
            if ((int)$page->qtype === 10) {
                $body .= \html_writer::tag('p', get_string('lessonessay', 'local_flatarchive'));
            }
            foreach ($answers[$page->id] ?? [] as $position => $answer) {
                $matching = (int)$page->qtype === 5;
                $pair = $matching && $position >= 2;
                $label = $pair ? 'lessonpair' : 'lessonanswer';
                if ($matching && !$pair) {
                    $label = $position === 0 ? 'lessoncorrect' : 'lessonincorrect';
                }
                $fragment = \html_writer::tag('h3', get_string($label, 'local_flatarchive'));
                $fragment .= self::text(
                    $answer->answer ?? '',
                    $answer->answerformat,
                    'page_answers',
                    $answer->id,
                    $cm,
                    $directory
                );
                if (trim($answer->response ?? '') !== '') {
                    $feedbacklabel = get_string($pair ? 'lessonmatch' : 'lessonfeedback', 'local_flatarchive');
                    $fragment .= \html_writer::tag('h4', $feedbacklabel);
                    $fragment .= self::text(
                        $answer->response,
                        $answer->responseformat,
                        'page_responses',
                        $answer->id,
                        $cm,
                        $directory
                    );
                }
                // Matching pairs do not carry individual jumps; the first two records do.
                if (!$pair) {
                    $fragment .= \html_writer::tag('p', self::jump(
                        (int)$answer->jumpto,
                        $page,
                        $pages,
                        $map,
                        $filename,
                        (int)$lesson->nextpagedefault
                    ));
                    if (!empty($lesson->custom) && (int)$page->qtype < 20) {
                        $fragment .= \html_writer::tag('p', get_string('lessonscore', 'local_flatarchive', $answer->score));
                    }
                }
                $body .= \html_writer::tag('section', $fragment, ['class' => 'fa-lesson-answer']);
            }
            $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $page->title, $body, '', $badges);
            \local_flatarchive_write_file($directory . '/' . $map[$page->id], $html);
            $files[] = $map[$page->id];
        }
        $content .= $items ? \html_writer::alist($items, ['class' => 'fa-lesson-pages'], 'ol') :
            \html_writer::tag('p', get_string('lessonempty', 'local_flatarchive'));
        // The optional lesson media file is authored content, unlike essay submission files.
        self::text('', FORMAT_HTML, 'mediafile', 0, $cm, $directory);
        $media = get_file_storage()->get_area_files($context->id, 'mod_lesson', 'mediafile', 0, 'filepath, filename', false);
        foreach ($media as $file) {
            $path = paths::assets((int)$cm->id, 'mediafile', 0) . $file->get_filepath() . $file->get_filename();
            $url = implode('/', array_map('rawurlencode', explode('/', $path)));
            $content .= \html_writer::tag('p', \html_writer::link($url, s($file->get_filename())));
        }
        $content .= \html_writer::tag('h2', get_string('lessonend', 'local_flatarchive'), ['id' => 'end']);
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $content, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        return ['filename' => $filename, 'filenames' => $files,
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name)) . $badges)];
    }

    /**
     * Follow the authored linked list, retaining orphan pages and stopping cycles without writes.
     *
     * @param array $pages Page records keyed by ID.
     * @return array Ordered records keyed by ID.
     */
    public static function ordered_pages(array $pages): array {
        $ordered = [];
        $roots = array_filter($pages, static function ($page) use ($pages) {
            return !isset($pages[$page->prevpageid]);
        });
        foreach ($roots + $pages as $start) {
            $id = $start->id;
            while (isset($pages[$id]) && !isset($ordered[$id])) {
                $ordered[$id] = $pages[$id];
                $id = $pages[$id]->nextpageid;
            }
        }
        return $ordered;
    }

    /**
     * Describe a jump without executing student-dependent routing.
     *
     * @param int $jump Stored destination.
     * @param \stdClass $page Origin page.
     * @param array $pages Allowed pages.
     * @param array $map Local filenames.
     * @param string $index Lesson index.
     * @param int $nextmode Flash-card navigation mode.
     * @return string Link or explicit unresolved destination.
     */
    private static function jump(
        int $jump,
        \stdClass $page,
        array $pages,
        array $map,
        string $index,
        int $nextmode
    ): string {
        if ($jump === -1 && $nextmode) {
            return get_string('lessondynamic', 'local_flatarchive');
        }
        if ($jump === -9 || ($jump === -1 && !$page->nextpageid)) {
            return \html_writer::link($index . '#end', get_string('lessonend', 'local_flatarchive'));
        }
        $target = $jump;
        if ($jump === 0) {
            $target = $page->id;
        } else if ($jump === -1) {
            $target = $page->nextpageid;
        } else if ($jump === -40) {
            $target = $page->prevpageid ?: $page->id;
        } else if ($jump < 0) {
            $keys = [-50 => 'unseenpageinbranch', -60 => 'randompageinbranch', -70 => 'randombranch', -80 => 'clusterjump'];
            $label = isset($keys[$jump]) ? get_string($keys[$jump], 'lesson') : (string)$jump;
            return s($label) . ' — ' . get_string('lessondynamic', 'local_flatarchive');
        }
        if (isset($pages[$target], $map[$target])) {
            return \html_writer::link($map[$target], get_string('lessongoto', 'local_flatarchive', s($pages[$target]->title)));
        }
        return get_string('lessonmissing', 'local_flatarchive');
    }

    /**
     * Copy only authored files and clean text without running site filters.
     *
     * @param string $text Source text.
     * @param int $format Moodle format.
     * @param string $area File area.
     * @param int $id File item ID.
     * @param \cm_info $cm Module.
     * @param string $directory Destination.
     * @return string Portable HTML.
     */
    private static function text(string $text, int $format, string $area, int $id, \cm_info $cm, string $directory): string {
        $context = \context_module::instance($cm->id);
        $text = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $text,
            $context,
            'mod_lesson',
            $area,
            $id,
            $directory,
            (int)$cm->id
        );
        return format_text($text, $format, ['context' => $context, 'filter' => false]);
    }
}
