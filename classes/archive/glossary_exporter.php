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
 * Archive approved glossary entries as a portable dictionary.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class glossary_exporter {
    /**
     * Write the glossary index, individual entry pages and attachments.
     *
     * @param \cm_info $cm Glossary module.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Archive directory.
     * @return array Index item, index filename and all generated HTML filenames.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'glossary') {
            throw new \coding_exception('The glossary exporter requires a glossary');
        }
        $context = \context_module::instance($cm->id);
        require_capability('mod/glossary:view', $context);
        $glossary = $DB->get_record('glossary', ['id' => $cm->instance], '*', MUST_EXIST);
        $intro = self::copy_area($glossary->intro, $context, 'intro', 0, $directory, (int)$cm->id);
        $content = format_text($intro, $glossary->introformat, ['context' => $context, 'filter' => false]);
        $content .= \html_writer::tag('p', get_string('glossaryscope', 'local_flatarchive'));
        $selection = '(glossaryid = :gid OR sourceglossaryid = :sourceid)';
        $params = ['gid' => $cm->instance, 'sourceid' => $cm->instance];
        $pending = $DB->count_records_select('glossary_entries', $selection . ' AND approved = 0', $params);
        if ($pending) {
            $content .= \html_writer::tag('p', get_string('glossaryexcluded', 'local_flatarchive', $pending));
        }
        $entries = $DB->get_recordset_select('glossary_entries', $selection . ' AND approved = 1', $params, 'concept, id');
        $toc = '';
        $filename = 'glossary-' . $cm->id . '.html';
        $filenames = [$filename];
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        try {
            foreach ($entries as $entry) {
                // Exported entries remain visible in the source glossary; their files live in the main glossary.
                $filecontext = $context;
                if ((int)$entry->glossaryid !== (int)$cm->instance) {
                    $maincm = get_coursemodule_from_instance('glossary', $entry->glossaryid, $cm->course, false, MUST_EXIST);
                    $filecontext = \context_module::instance($maincm->id);
                }
                $entryfile = 'glossary-' . $cm->id . '-entry-' . $entry->id . '.html';
                $toc .= \html_writer::tag('li', \html_writer::link($entryfile, s($entry->concept)));
                $definition = self::copy_area(
                    $entry->definition,
                    $filecontext,
                    'entry',
                    (int)$entry->id,
                    $directory,
                    (int)$cm->id
                );
                $body = \html_writer::tag('p', \html_writer::link(
                    $filename,
                    get_string('backtoglossary', 'local_flatarchive')
                ));
                $body .= \html_writer::tag('p', get_string('glossaryauthor', 'local_flatarchive') . ': ' .
                    s(self::author((int)$entry->userid)));
                $body .= tags::render('mod_glossary', 'glossary_entries', (int)$entry->id);
                $body .= format_text($definition, $entry->definitionformat, ['context' => $filecontext, 'filter' => false]);
                $aliases = $DB->get_fieldset_select('glossary_alias', 'alias', 'entryid = ?', [$entry->id]);
                $categories = $DB->get_fieldset_sql(
                    'SELECT c.name FROM {glossary_categories} c
                       JOIN {glossary_entries_categories} ec ON ec.categoryid = c.id
                      WHERE ec.entryid = ? AND c.glossaryid = ? ORDER BY c.name, c.id',
                    [$entry->id, $cm->instance]
                );
                foreach (['glossaryaliases' => $aliases, 'glossarycategories' => $categories] as $key => $values) {
                    if ($values) {
                        sort($values, SORT_NATURAL | SORT_FLAG_CASE);
                        $body .= \html_writer::tag('p', get_string($key, 'local_flatarchive') . ': ' . s(implode(', ', $values)));
                    }
                }
                self::copy_area('', $filecontext, 'attachment', (int)$entry->id, $directory, (int)$cm->id);
                $files = get_file_storage()->get_area_files(
                    $filecontext->id,
                    'mod_glossary',
                    'attachment',
                    $entry->id,
                    'filepath, filename',
                    false
                );
                $links = '';
                foreach ($files as $file) {
                    $path = paths::assets((int)$cm->id, 'attachment', (int)$entry->id);
                    $path .= $file->get_filepath() . $file->get_filename();
                    $url = implode('/', array_map('rawurlencode', explode('/', $path)));
                    $links .= \html_writer::tag('li', \html_writer::link($url, s($file->get_filename())));
                }
                if ($links !== '') {
                    $body .= \html_writer::tag('h2', get_string('glossaryattachments', 'local_flatarchive'));
                    $body .= \html_writer::tag('ul', $links);
                }
                $body .= self::comments($cm, $context, $glossary, (int)$entry->id);
                $html = \local_flatarchive_render_page_template(
                    $modinfo->get_course(),
                    $cm,
                    $entry->concept,
                    $body,
                    '',
                    $badges
                );
                \local_flatarchive_write_file($directory . '/' . $entryfile, $html);
                $filenames[] = $entryfile;
            }
        } finally {
            $entries->close();
        }
        if ($toc === '') {
            $content .= \html_writer::tag('p', get_string('glossaryempty', 'local_flatarchive'));
        } else {
            $content .= \html_writer::tag('nav', \html_writer::tag('ul', $toc), [
                'aria-label' => get_string('glossaryentries', 'local_flatarchive'),
            ]);
        }

        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $content, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        return [
            'filename' => $filename,
            'filenames' => $filenames,
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name)) . $badges),
        ];
    }

    /**
     * Resolve a display name without exporting profile details or deleted identities.
     *
     * @param int $userid User ID.
     * @return string Display name.
     */
    private static function author(int $userid): string {
        global $DB;
        $fields = 'id, deleted, ' . implode(', ', \core_user\fields::get_name_fields());
        $user = $DB->get_record('user', ['id' => $userid], $fields);
        if (!$user || $user->deleted) {
            return get_string('glossaryunknownauthor', 'local_flatarchive');
        }
        return fullname($user);
    }

    /**
     * Export all readable comments in chronological order in the selected glossary context.
     *
     * @param \cm_info $cm Selected module.
     * @param \context_module $context Selected context (not the attachment storage context).
     * @param \stdClass $glossary Glossary settings.
     * @param int $entryid Approved entry ID.
     * @return string Static comments section, or an empty string.
     */
    private static function comments(
        \cm_info $cm,
        \context_module $context,
        \stdClass $glossary,
        int $entryid
    ): string {
        global $CFG, $DB;
        if (
            empty($CFG->usecomments) || !$glossary->allowcomments ||
            !has_capability('mod/glossary:comment', $context)
        ) {
            return '';
        }
        require_once($CFG->dirroot . '/comment/lib.php');
        $options = (object)[
            'context' => $context, 'component' => 'mod_glossary', 'courseid' => $cm->course,
            'cm' => $cm, 'area' => 'glossary_entry', 'itemid' => $entryid,
        ];
        $manager = new \comment($options);
        if (!$manager->can_view()) {
            return '';
        }
        // Read raw content to avoid applying dynamic Moodle filters to archived comments.
        $records = $DB->get_recordset_select(
            'comments',
            'contextid = :contextid AND commentarea = :area AND itemid = :itemid AND ' .
            '(component = :component OR component IS NULL)',
            ['contextid' => $context->id, 'area' => 'glossary_entry',
                'itemid' => $entryid, 'component' => 'mod_glossary'],
            'timecreated, id'
        );
        $items = '';
        try {
            foreach ($records as $record) {
                $date = \html_writer::tag('time', s(userdate($record->timecreated)), [
                    'datetime' => gmdate('Y-m-d\\TH:i:s\\Z', $record->timecreated),
                ]);
                $metadata = \html_writer::tag('p', s(self::author((int)$record->userid)) . ' — ' . $date);
                $text = format_text($record->content, $record->format, ['context' => $context, 'filter' => false]);
                $items .= \html_writer::tag('li', $metadata . $text);
            }
        } finally {
            $records->close();
        }
        if ($items === '') {
            return '';
        }
        return \html_writer::tag('h2', get_string('glossarycomments', 'local_flatarchive')) .
            \html_writer::tag('ol', $items, ['class' => 'fa-glossary-comments']);
    }

    /**
     * Copy one file area into a directory isolated by selected module and entry ID.
     *
     * @param string $html HTML containing Moodle file placeholders.
     * @param \context_module $context Storage context.
     * @param string $area File area.
     * @param int $itemid Entry ID, or zero for the introduction.
     * @param string $directory Archive directory.
     * @param int $cmid Selected module ID.
     * @return string Rewritten HTML.
     */
    private static function copy_area(
        string $html,
        \context_module $context,
        string $area,
        int $itemid,
        string $directory,
        int $cmid
    ): string {
        return \local_flatarchive_copy_pluginfiles_and_rewrite(
            $html,
            $context,
            'mod_glossary',
            $area,
            $itemid,
            $directory,
            $cmid
        );
    }
}
