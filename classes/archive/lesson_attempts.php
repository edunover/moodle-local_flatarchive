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
 * Export visible lesson reports without grading or updating attempts.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lesson_attempts {
    /**
     * Write a report page for each visible user's retry.
     *
     * @param \cm_info $cm Module.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Output directory.
     * @param array $pages Authored pages keyed by ID.
     * @param array $answers Authored answers grouped by page ID.
     * @return array Report index HTML and generated filenames.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory, array $pages, array $answers): array {
        global $DB;
        $context = \context_module::instance($cm->id);
        $heading = \html_writer::tag('h2', get_string('lessonattempts', 'local_flatarchive'));
        if (!has_capability('mod/lesson:viewreports', $context)) {
            return ['html' => $heading . \html_writer::tag('p', get_string('lessonreportsdenied', 'local_flatarchive')),
                'files' => []];
        }
        $group = groups_get_activity_groupmode($cm) ? groups_get_activity_group($cm) : 0;
        $fields = 'u.id, u.deleted, ' . implode(', ', \core_user\fields::get_name_fields());
        $users = get_enrolled_users($context, '', $group, $fields, 'u.id', 0, 0, true);
        $files = [];
        $links = [];
        foreach ($users as $user) {
            if ($user->deleted || !groups_user_groups_visible($cm->course, $user->id, $cm)) {
                continue;
            }
            $params = ['lessonid' => $cm->instance, 'userid' => $user->id];
            $rows = $DB->get_records('lesson_attempts', $params, 'retry, timeseen, id');
            $branches = $DB->get_records('lesson_branch', $params, 'retry, timeseen, id');
            $timers = array_values($DB->get_records('lesson_timer', $params, 'starttime, id'));
            $grades = array_values($DB->get_records('lesson_grades', $params, 'completed, id'));
            $tries = [];
            foreach ($rows as $row) {
                $tries[(int)$row->retry]['answers'][] = $row;
            }
            foreach ($branches as $branch) {
                $tries[(int)$branch->retry]['branches'][] = $branch;
            }
            // Moodle associates timers and stored grades with retries by chronological position.
            foreach ($timers as $retry => $timer) {
                $tries[$retry]['timer'] = $timer;
            }
            foreach ($grades as $retry => $grade) {
                $tries[$retry]['grade'] = $grade;
            }
            ksort($tries);
            foreach ($tries as $retry => $data) {
                $title = fullname($user, has_capability('moodle/site:viewfullnames', $context)) . ' — ' .
                    get_string('lessonattemptnumber', 'local_flatarchive', $retry + 1);
                $file = 'lesson-' . $cm->id . '-user-' . $user->id . '-attempt-' . ($retry + 1) . '.html';
                $body = \html_writer::tag('p', \html_writer::link(
                    'lesson-' . $cm->id . '.html',
                    get_string('backtolesson', 'local_flatarchive')
                ));
                $timer = $data['timer'] ?? null;
                $grade = $data['grade'] ?? null;
                $status = $grade || ($timer && $timer->completed) ? 'lessoncompleted' : 'lessonincomplete';
                $body .= \html_writer::tag('p', get_string($status, 'local_flatarchive'));
                if ($timer) {
                    $body .= self::date('lessonstarted', (int)$timer->starttime);
                    $body .= self::date('lessonlastactivity', (int)$timer->lessontime);
                }
                if ($grade) {
                    $body .= self::date('lessoncompleted', (int)$grade->completed);
                    $gradelabel = get_string('lessonrecordedgrade', 'local_flatarchive', s(format_float($grade->grade, 2)));
                    $body .= \html_writer::tag('p', $gradelabel);
                }
                foreach ($data['answers'] ?? [] as $attempt) {
                    if (!isset($pages[$attempt->pageid])) {
                        $body .= \html_writer::tag('p', get_string('lessonmissing', 'local_flatarchive'));
                        continue;
                    }
                    $page = $pages[$attempt->pageid];
                    $body .= \html_writer::tag('h3', \html_writer::link(
                        'lesson-' . $cm->id . '-page-' . $page->id . '.html',
                        s($page->title)
                    ));
                    $body .= self::date('lessonanswered', (int)$attempt->timeseen);
                    $body .= self::response($attempt, $page, $answers[$page->id] ?? [], $cm, $directory);
                }
                $visits = [];
                foreach ($data['branches'] ?? [] as $branch) {
                    if (isset($pages[$branch->pageid])) {
                        $visits[] = \html_writer::link(
                            'lesson-' . $cm->id . '-page-' . $branch->pageid . '.html',
                            s($pages[$branch->pageid]->title)
                        ) . ' — ' . s(userdate($branch->timeseen));
                    }
                }
                if ($visits) {
                    $body .= \html_writer::tag('h3', get_string('lessonvisited', 'local_flatarchive')) .
                        \html_writer::alist($visits);
                }
                $html = \local_flatarchive_render_page_template(
                    $modinfo->get_course(),
                    $cm,
                    $title,
                    $body,
                    '',
                    \local_flatarchive_visibility_badges($cm, $modinfo)
                );
                \local_flatarchive_write_file($directory . '/' . $file, $html);
                $files[] = $file;
                $links[] = \html_writer::link($file, s($title));
            }
        }
        $scope = \html_writer::tag('p', get_string('lessonreportsscope', 'local_flatarchive'));
        if ($group) {
            $scope .= \html_writer::tag('p', s(groups_get_group_name($group)));
        }
        return ['html' => $heading . $scope . ($links ? \html_writer::alist($links) :
            \html_writer::tag('p', get_string('lessonnoattempts', 'local_flatarchive'))), 'files' => $files];
    }

    /**
     * Render the recorded response, never recompute its correctness.
     *
     * @param \stdClass $attempt Recorded question attempt.
     * @param \stdClass $page Authored question.
     * @param array $answers Answers belonging to that question.
     * @param \cm_info $cm Module.
     * @param string $directory Output directory.
     * @return string Static response.
     */
    private static function response(\stdClass $attempt, \stdClass $page, array $answers, \cm_info $cm, string $directory): string {
        $body = '';
        if ((int)$page->qtype === 10) {
            // Do not instantiate arbitrary classes stored in serialized user input.
            $essay = @unserialize($attempt->useranswer ?? '', ['allowed_classes' => ['stdClass']]);
            if (!$essay instanceof \stdClass) {
                return \html_writer::tag('p', get_string('lessonunreadable', 'local_flatarchive'));
            }
            $body .= self::text(
                (string)($essay->answer ?? ''),
                (int)($essay->answerformat ?? FORMAT_HTML),
                'essay_answers',
                (int)$attempt->id,
                $cm,
                $directory
            );
            if (!empty($essay->response)) {
                $body .= \html_writer::tag('h4', get_string('lessonfeedback', 'local_flatarchive'));
                $body .= self::text(
                    (string)$essay->response,
                    (int)($essay->responseformat ?? FORMAT_HTML),
                    'essay_responses',
                    (int)$attempt->id,
                    $cm,
                    $directory
                );
            }
            $body .= \html_writer::tag('p', empty($essay->graded) ? get_string('lessonungraded', 'local_flatarchive') :
                get_string('lessonearnedscore', 'local_flatarchive', s((string)($essay->score ?? 0))));
            return $body;
        }
        $status = get_string($attempt->correct ? 'lessonanswercorrect' : 'lessonanswerincorrect', 'local_flatarchive');
        $body .= \html_writer::tag('p', $status);
        if (in_array((int)$page->qtype, [2, 3], true)) {
            $ids = (int)$page->qtype === 3 && $page->qoption ? explode(',', $attempt->useranswer ?? '') : [$attempt->answerid];
            foreach ($answers as $answer) {
                if (in_array((string)$answer->id, array_map('strval', $ids), true)) {
                    $body .= self::text(
                        $answer->answer ?? '',
                        (int)$answer->answerformat,
                        'page_answers',
                        (int)$answer->id,
                        $cm,
                        $directory
                    );
                }
            }
        } else if ((int)$page->qtype === 5) {
            $values = explode(',', $attempt->useranswer ?? '');
            foreach (array_slice($answers, 2) as $position => $answer) {
                $body .= self::text(
                    $answer->answer ?? '',
                    (int)$answer->answerformat,
                    'page_answers',
                    (int)$answer->id,
                    $cm,
                    $directory
                );
                $body .= \html_writer::tag('p', s($values[$position] ?? ''));
            }
        } else {
            $body .= \html_writer::tag('p', nl2br(s($attempt->useranswer ?? '')));
        }
        return $body;
    }

    /**
     * Render a labelled stored timestamp.
     *
     * @param string $label Language key.
     * @param int $time Timestamp.
     * @return string HTML.
     */
    private static function date(string $label, int $time): string {
        return $time ? \html_writer::tag('p', get_string($label, 'local_flatarchive') . ': ' . s(userdate($time))) : '';
    }

    /**
     * Copy files only after report, enrolment and group checks.
     *
     * @param string $text Recorded content.
     * @param int $format Format.
     * @param string $area File area.
     * @param int $id Item ID.
     * @param \cm_info $cm Module.
     * @param string $directory Destination.
     * @return string Clean HTML.
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
