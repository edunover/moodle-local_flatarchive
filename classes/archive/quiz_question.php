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
 * Convert supported Moodle question renderers to passive, portable HTML.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class quiz_question {
    /**
     * Types with a verified passive representation.
     *
     * @param string $type Question type.
     * @return bool Supported.
     */
    public static function supported(string $type): bool {
        return in_array($type, ['description', 'multichoice', 'truefalse', 'shortanswer', 'numerical', 'match', 'essay'], true);
    }

    /**
     * Render the exact question version and response stored in a usage.
     *
     * @param \question_attempt $qa Recorded or in-memory question.
     * @param \question_display_options $options Authorised display options.
     * @param string $number Question number.
     * @param \cm_info $cm Module.
     * @param string $directory Destination.
     * @return string Passive HTML.
     */
    public static function render(
        \question_attempt $qa,
        \question_display_options $options,
        string $number,
        \cm_info $cm,
        string $directory
    ): string {
        $question = $qa->get_question();
        $options->readonly = true;
        $options->context = \context_module::instance($cm->id);
        $options->flags = \question_display_options::HIDDEN;
        $options->history = \question_display_options::HIDDEN;
        $options->manualcommentlink = null;
        $options->questionreviewlink = null;
        $options->editquestionparams = [];
        $options->versioninfo = false;
        if (!self::supported($question->qtype->name())) {
            $body = \html_writer::tag('p', get_string('quizunsupported', 'local_flatarchive', s($question->qtype->name())));
            $body .= \html_writer::tag('p', s($qa->get_question_summary() ?? ''));
            $body .= \html_writer::tag('p', s($qa->get_response_summary() ?? ''));
            if ($options->rightanswer) {
                $body .= \html_writer::tag('p', s($qa->get_right_answer_summary() ?? ''));
            }
            return \html_writer::tag('section', $body, ['class' => 'fa-quiz-question']);
        }
        if (!$qa->get_usage_id()) {
            // Authored questionnaire: no unfinished-attempt behaviour should hide its solutions.
            global $PAGE;
            $renderer = $question->get_renderer($PAGE);
            $html = $renderer->formulation_and_controls($qa, $options) . $renderer->feedback($qa, $options);
        } else {
            $html = $qa->render($options, $number);
        }
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8"><div id="fa-question-root">' . $html . '</div>', LIBXML_NONET);
        $xpath = new \DOMXPath($dom);
        foreach (iterator_to_array($xpath->query('//script|//style|//button|//input[@type="hidden"]')) as $node) {
            $node->parentNode->removeChild($node);
        }
        foreach (iterator_to_array($xpath->query('//input|//textarea|//select')) as $node) {
            $value = '';
            if ($node->nodeName === 'textarea') {
                $value = $node->textContent;
            } else if ($node->nodeName === 'select') {
                foreach ($node->getElementsByTagName('option') as $option) {
                    if ($option->hasAttribute('selected') || !$qa->get_usage_id()) {
                        $value .= $option->textContent . ' ';
                    }
                }
            } else if (in_array($node->getAttribute('type'), ['radio', 'checkbox'], true)) {
                $value = $node->hasAttribute('checked') ? '☑ ' : '☐ ';
            } else if (!in_array($node->getAttribute('type'), ['submit', 'reset', 'button'], true)) {
                $value = $node->getAttribute('value');
            }
            $replacement = $dom->createElement('span');
            $replacement->setAttribute('class', 'fa-quiz-response');
            $replacement->appendChild($dom->createTextNode($value));
            $node->parentNode->replaceChild($replacement, $node);
        }
        foreach (iterator_to_array($xpath->query('//*[@src or @href or @poster or @data]')) as $node) {
            foreach (['src', 'href', 'poster', 'data'] as $attribute) {
                if (!$node->hasAttribute($attribute)) {
                    continue;
                }
                $url = $node->getAttribute($attribute);
                $local = self::file($url, $qa, $options, $cm, $directory);
                if ($local !== null) {
                    if ($local === '') {
                        $node->removeAttribute($attribute);
                    } else {
                        $node->setAttribute($attribute, $local);
                    }
                }
            }
            $node->removeAttribute('srcset');
        }
        foreach (iterator_to_array($xpath->query('//*')) as $node) {
            foreach (iterator_to_array($node->attributes) as $attribute) {
                if (str_starts_with(strtolower($attribute->name), 'on') || str_starts_with($attribute->name, 'data-')) {
                    $node->removeAttribute($attribute->name);
                }
            }
        }
        foreach (iterator_to_array($xpath->query('//form')) as $node) {
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
        }
        $body = '';
        foreach ($dom->getElementById('fa-question-root')->childNodes as $node) {
            $body .= $dom->saveHTML($node);
        }
        $body = clean_text($body, FORMAT_HTML);
        return \html_writer::tag('section', $body, ['class' => 'fa-quiz-question']);
    }

    /**
     * Copy only files referenced by this question and permitted by its display options.
     *
     * @param string $url Rendered URL.
     * @param \question_attempt $qa Question.
     * @param \question_display_options $options Permissions.
     * @param \cm_info $cm Module.
     * @param string $directory Destination.
     * @return string|null Local path, empty for unavailable Moodle assets, null for other URLs.
     */
    private static function file(
        string $url,
        \question_attempt $qa,
        \question_display_options $options,
        \cm_info $cm,
        string $directory
    ): ?string {
        global $CFG;
        $prefix = $CFG->wwwroot . '/pluginfile.php';
        if (!str_starts_with($url, $prefix)) {
            // Moodle UI icons and action links do not belong in a standalone question.
            return str_starts_with($url, $CFG->wwwroot . '/') ? '' : null;
        }
        $suffix = substr($url, strlen($prefix));
        if (str_starts_with($suffix, '?')) {
            parse_str(substr($suffix, 1), $query);
            $path = $query['file'] ?? '';
        } else {
            $path = rawurldecode(explode('?', $suffix, 2)[0]);
        }
        $parts = explode('/', ltrim($path, '/'));
        if (count($parts) < 7) {
            return '';
        }
        [$contextid, $component, $area, $usage, $slot, $itemid] = array_splice($parts, 0, 6);
        if ((string)$usage !== (string)$qa->get_usage_id() || (int)$slot !== (int)$qa->get_slot()) {
            return '';
        }
        $isresponse = $component === 'question' && str_starts_with($area, 'response_');
        $expectedcontext = $isresponse ? \context_module::instance($cm->id)->id : $qa->get_question()->contextid;
        if (
            (int)$contextid !== (int)$expectedcontext ||
            !$qa->check_file_access($options, $component, $area, array_merge([$itemid], $parts), false)
        ) {
            return '';
        }
        $filename = array_pop($parts);
        $filepath = '/' . ($parts ? implode('/', $parts) . '/' : '');
        $file = get_file_storage()->get_file($contextid, $component, $area, $itemid, $filepath, $filename);
        if (!$file || $file->is_directory()) {
            return '';
        }
        $relative = 'files/' . $cm->id . '/quiz/' . $file->get_pathnamehash() . '/' . clean_filename($filename);
        $destination = $directory . '/' . $relative;
        if (!is_file($destination)) {
            make_writable_directory(dirname($destination));
            if (!$file->copy_content_to($destination)) {
                throw new \moodle_exception('archivefailed', 'local_flatarchive');
            }
        }
        return implode('/', array_map('rawurlencode', explode('/', $relative)));
    }
}
