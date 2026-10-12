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
 * Portable Database list and single-entry templates.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class data_exporter {
    /**
     * Export every entry for a user allowed to manage Database entries.
     *
     * @param \cm_info $cm Module
     * @param \course_modinfo $modinfo Course structure
     * @param string $directory Destination
     * @return array Generated pages and course index item
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/data/lib.php');
        require_once($CFG->dirroot . '/mod/data/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'data') {
            throw new \coding_exception('The Database exporter requires mod_data');
        }
        $context = \context_module::instance($cm->id);
        require_capability('mod/data:viewentry', $context);
        require_capability('mod/data:manageentries', $context);
        if (!$cm->uservisible) {
            throw new \moodle_exception('activityiscurrentlyhidden');
        }
        $data = $DB->get_record('data', ['id' => $cm->instance], '*', MUST_EXIST);
        // An instructor archive includes all groups and approval states, independent of learner restrictions.
        $records = $DB->get_records('data_records', ['dataid' => $data->id], 'timecreated, id');
        $fields = $DB->get_records('data_fields', ['dataid' => $data->id], 'id');
        $filename = 'data-' . $cm->id . '.html';
        $filenames = [$filename];
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        $intro = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $data->intro,
            $context,
            'mod_data',
            'intro',
            0,
            $directory,
            $cm->id
        );
        $list = format_text($intro, $data->introformat, ['context' => $context, 'filter' => false]);
        if ($data->requiredentriestoview > 0) {
            $list .= \html_writer::tag('p', get_string('datarequiredentries', 'local_flatarchive', $data->requiredentriestoview));
        }
        $list .= $data->listtemplateheader;
        foreach ($records as $record) {
            $entryfile = 'data-' . $cm->id . '-entry-' . $record->id . '.html';
            $status = !$record->approved ? \html_writer::span(
                get_string('datanotapproved', 'local_flatarchive'),
                'fa-data-unapproved'
            ) : '';
            $list .= self::entry($data, $record, $fields, 'listtemplate', $entryfile, $status, $cm, $directory);
            $body = \html_writer::tag('p', \html_writer::link($filename, get_string('backtodata', 'local_flatarchive')));
            $body .= $status;
            $body .= self::entry($data, $record, $fields, 'singletemplate', $entryfile, '', $cm, $directory);
            $body .= self::comments($cm, $data, $record);
            self::write($entryfile, $body, $data, $cm, $modinfo, $directory, $badges);
            $filenames[] = $entryfile;
        }
        $list .= $data->listtemplatefooter;
        if (!$records) {
            $list .= \html_writer::tag('p', get_string('dataempty', 'local_flatarchive'));
        }
        self::write($filename, $list, $data, $cm, $modinfo, $directory, $badges);
        return [
            'filename' => $filename,
            'filenames' => $filenames,
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name)) . $badges),
        ];
    }

    /**
     * Substitute passive field values and local navigation without editing the saved template.
     *
     * @param \stdClass $data Database
     * @param \stdClass $record Entry
     * @param array $fields Field definitions
     * @param string $view Template property
     * @param string $entryfile Local entry URL
     * @param string $status Approval badge for the list
     * @param \cm_info $cm Module
     * @param string $directory Destination
     * @return string Template HTML
     */
    private static function entry(
        \stdClass $data,
        \stdClass $record,
        array $fields,
        string $view,
        string $entryfile,
        string $status,
        \cm_info $cm,
        string $directory
    ): string {
        global $DB;
        $template = $data->$view;
        $replacements = [];
        $fallback = '';
        foreach ($fields as $definition) {
            $field = data_get_field($definition, $data);
            $value = $field ? (string)$field->display_browse_field($record->id, $view) : '';
            $content = $DB->get_record('data_content', ['fieldid' => $definition->id, 'recordid' => $record->id]);
            if ($content) {
                if ($definition->type === 'picture') {
                    $url = new \moodle_url('/mod/data/view.php', ['d' => $data->id, 'rid' => $record->id]);
                    $value = str_replace([$url->out(), $url->out(false)], $entryfile, $value);
                    $contextid = \context_module::instance($cm->id)->id;
                    $fs = get_file_storage();
                    if (!$fs->file_exists($contextid, 'mod_data', 'content', $content->id, '/', 'thumb_' . $content->content)) {
                        $value = str_replace('thumb_' . rawurlencode($content->content), rawurlencode($content->content), $value);
                    }
                } else if ($definition->type === 'file') {
                    // The file link remains; its theme-hosted decorative icon would require the live site.
                    $value = preg_replace('/<img\b[^>]*>/i', '', $value);
                }
                $value = self::files($value, $content->id, $cm, $directory);
            }
            $replacements['[[' . $definition->name . ']]'] = $value;
            foreach (['name', 'description', 'id'] as $property) {
                $replacements['[[' . $definition->name . '#' . $property . ']]'] = s($definition->$property);
            }
            $fallback .= \html_writer::tag('dt', s($definition->name)) . \html_writer::tag('dd', $value);
        }
        if (trim($template) === '') {
            $template = \html_writer::tag('dl', $fallback) . '##more##';
        }
        $more = \html_writer::link($entryfile, get_string('dataviewentry', 'local_flatarchive')) . ' ' . $status;
        $user = $DB->get_record('user', ['id' => $record->userid]);
        $replacements += [
            '##more##' => $more,
            '##moreurl##' => $entryfile,
            '##actionsmenu##' => $view === 'listtemplate' ? $more : '',
            '##user##' => $user && !$user->deleted ? s(fullname($user)) : '',
            '##timeadded##' => s(userdate($record->timecreated)),
            '##timemodified##' => s(userdate($record->timemodified)),
            '##tags##' => tags::render('mod_data', 'data_records', (int)$record->id),
            '##approvalstatus##' => !$record->approved ? 'fa-data-pending' : '',
        ];
        // Replace template tokens once; values must never be interpreted as template commands.
        $html = preg_replace_callback('/\[\[[^\[\]]+\]\]|##[a-z]+##/i', static function ($match) use ($replacements) {
            foreach ($replacements as $token => $value) {
                if (strcasecmp($match[0], $token) === 0) {
                    return $value;
                }
            }
            return '';
        }, $template);
        if ($view === 'listtemplate' && !str_contains($template, '##more##') && !str_contains($template, '##actionsmenu##')) {
            // Inject inside a table cell when the row template is tabular, preserving valid table markup.
            $suffix = \html_writer::span($more, 'fa-data-entry-link');
            if (preg_match('/<\/t[dh]>/i', $html)) {
                $html = preg_replace('/(<\/t[dh]>)/i', $suffix . '$1', $html, 1);
            } else {
                $html .= $suffix;
            }
        }
        return $html;
    }

    /**
     * Copy only the current authorized entry's field files and rewrite native URLs.
     *
     * @param string $html Rendered field
     * @param int $contentid Field content ID
     * @param \cm_info $cm Module
     * @param string $directory Destination
     * @return string Local field HTML
     */
    private static function files(string $html, int $contentid, \cm_info $cm, string $directory): string {
        $context = \context_module::instance($cm->id);
        $html = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $html,
            $context,
            'mod_data',
            'content',
            $contentid,
            $directory,
            $cm->id
        );
        foreach (get_file_storage()->get_area_files($context->id, 'mod_data', 'content', $contentid, 'id', false) as $file) {
            $url = \moodle_url::make_pluginfile_url(
                $context->id,
                'mod_data',
                'content',
                $contentid,
                $file->get_filepath(),
                $file->get_filename()
            );
            $path = paths::assets($cm->id, 'content', $contentid) . $file->get_filepath() . $file->get_filename();
            $path = implode('/', array_map('rawurlencode', explode('/', $path)));
            $html = str_replace([$url->out(), $url->out(false)], $path, $html);
        }
        return $html;
    }

    /**
     * Read entry comments for the complete instructor archive.
     *
     * @param \cm_info $cm Module
     * @param \stdClass $data Database
     * @param \stdClass $record Entry
     * @return string Comments
     */
    private static function comments(\cm_info $cm, \stdClass $data, \stdClass $record): string {
        global $CFG, $DB;
        if (empty($CFG->usecomments) || !$data->comments) {
            return '';
        }
        $context = \context_module::instance($cm->id);
        $comments = $DB->get_records_select(
            'comments',
            'contextid = :contextid AND commentarea = :area AND itemid = :itemid AND ' .
            '(component = :component OR component IS NULL)',
            ['contextid' => $context->id, 'area' => 'database_entry', 'itemid' => $record->id, 'component' => 'mod_data'],
            'timecreated, id'
        );
        $items = '';
        foreach ($comments as $comment) {
            $user = $DB->get_record('user', ['id' => $comment->userid]);
            $author = $user && !$user->deleted ? s(fullname($user)) : '';
            $content = format_text($comment->content, $comment->format, ['context' => $context, 'filter' => false]);
            $items .= \html_writer::tag('li', $author . ' — ' . s(userdate($comment->timecreated)) . $content);
        }
        return $items === '' ? '' : \html_writer::tag('h2', get_string('comments')) . \html_writer::tag('ul', $items);
    }

    /**
     * Save passive HTML and an activity-specific stylesheet.
     *
     * @param string $filename Local page name
     * @param string $body Body HTML
     * @param \stdClass $data Database
     * @param \cm_info $cm Module
     * @param \course_modinfo $modinfo Course structure
     * @param string $directory Destination
     * @param string $badges Visibility badges
     */
    private static function write(
        string $filename,
        string $body,
        \stdClass $data,
        \cm_info $cm,
        \course_modinfo $modinfo,
        string $directory,
        string $badges
    ): void {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div class="fa-data-body">' . $body . '</div>');
        $xpath = new \DOMXPath($document);
        // HTML Purifier does not understand every HTML5 layout element on older Moodle versions.
        // Temporarily use divs, sanitizing their attributes, then restore these inert structural tags.
        $structural = [];
        foreach ($xpath->query('//article|//section|//header|//footer|//main|//nav|//aside|//figure|//figcaption') as $node) {
            $marker = 'fa-structure-' . bin2hex(random_bytes(12));
            $structural[$marker] = [$node->tagName, $node->getAttribute('id'), $node->getAttribute('class')];
            $replacement = $document->createElement('div');
            foreach ($node->attributes as $attribute) {
                $replacement->setAttribute($attribute->name, $attribute->value);
            }
            $replacement->setAttribute('class', $node->getAttribute('class') . ' ' . $marker);
            while ($node->firstChild) {
                $replacement->appendChild($node->firstChild);
            }
            $node->parentNode->replaceChild($replacement, $node);
        }
        $root = $xpath->query('//div[@class="fa-data-body"]')->item(0);
        $clean = clean_text($document->saveHTML($root), FORMAT_HTML);
        $document->loadHTML('<?xml encoding="UTF-8">' . $clean);
        $xpath = new \DOMXPath($document);
        foreach ($structural as $marker => [$tag, $originalid, $originalclass]) {
            $node = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ' . $marker . ' ")]')->item(0);
            if (!$node) {
                continue;
            }
            $replacement = $document->createElement($tag);
            $node->removeAttribute('id');
            $node->setAttribute('class', $originalclass);
            foreach ($node->attributes as $attribute) {
                $replacement->setAttribute($attribute->name, $attribute->value);
            }
            if ($originalid !== '') {
                $replacement->setAttribute('id', $originalid);
            }
            while ($node->firstChild) {
                $replacement->appendChild($node->firstChild);
            }
            $node->parentNode->replaceChild($replacement, $node);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);
        // Editing widgets and executable template code have no role in a static archive.
        foreach ($xpath->query('//script|//form|//input|//button|//select|//textarea') as $node) {
            $node->parentNode->removeChild($node);
        }
        $body = '';
        $root = $xpath->query('//div[@class="fa-data-body"]')->item(0);
        foreach ($root->childNodes as $child) {
            $body .= $document->saveHTML($child);
        }
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $body, '', $badges);
        // A separate file prevents template CSS from changing any other archived activity.
        $cssfile = 'files/' . $cm->id . '/database.css';
        make_writable_directory($directory . '/files/' . $cm->id);
        \local_flatarchive_write_file($directory . '/' . $cssfile, $data->csstemplate);
        $html = str_replace('</head>', '<link rel="stylesheet" href="' . $cssfile . '"></head>', $html);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
    }
}
