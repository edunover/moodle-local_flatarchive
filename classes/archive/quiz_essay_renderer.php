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
 * Read-only native essay content without invoking plagiarism integrations.
 *
 * Adapted from Moodle's qtype_essay_renderer (The Open University, 2009).
 * The caller loads the native renderer before constructing this export-only subclass.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class quiz_essay_renderer extends \qtype_essay_renderer {
    /**
     * Render the question and saved response using Moodle's native response format renderer.
     *
     * @param \question_attempt $qa Question attempt, recorded or in memory
     * @param \question_display_options $options Authorised read-only display options
     * @return string Passive essay body
     */
    public function formulation_and_controls(\question_attempt $qa, \question_display_options $options): string {
        if (!$options->readonly) {
            throw new \coding_exception('The archive essay renderer only supports read-only output');
        }
        $question = $qa->get_question();
        $responseoutput = $question->get_format_renderer($this->page);
        $responseoutput->set_displayoptions($options);
        $step = $qa->get_last_step_with_qt_var('answer');
        $answer = $responseoutput->response_area_read_only(
            'answer',
            $qa,
            $step,
            $question->responsefieldlines,
            $options->context
        );
        $answer .= \html_writer::nonempty_tag('p', $question->get_word_count_message_for_review($step->get_qt_data()));
        $body = \html_writer::div($answer, 'answer');
        if ($qa->get_state() === \question_state::$invalid) {
            $body .= \html_writer::nonempty_tag('div', $question->get_validation_error($step->get_qt_data()), [
                'class' => 'validationerror',
            ]);
        }
        if ($question->attachments) {
            $body .= \html_writer::div($this->files_read_only($qa, $options), 'attachments');
        }
        return \html_writer::div($question->format_questiontext($qa), 'qtext') . \html_writer::div($body, 'ablock');
    }

    /**
     * Link response attachments without adding plagiarism reports or live theme icons.
     *
     * @param \question_attempt $qa Question attempt
     * @param \question_display_options $options Authorised display options
     * @return string File list; the caller checks access and copies each referenced file locally
     */
    public function files_read_only(\question_attempt $qa, \question_display_options $options): string {
        $items = '';
        foreach ($qa->get_last_qt_files('attachments', $options->context->id) as $file) {
            $items .= \html_writer::tag('li', \html_writer::link($qa->get_response_file_url($file), s($file->get_filename())));
        }
        if ($items === '') {
            return '';
        }
        $labelid = $qa->get_qt_field_name('attachments') . '_label';
        $label = $options->add_question_identifier_to_label(get_string('answerfiles', 'qtype_essay'));
        return \html_writer::tag('h4', $label, ['id' => $labelid]) .
            \html_writer::tag('ul', $items, ['aria-labelledby' => $labelid, 'class' => 'list-unstyled']);
    }
}
