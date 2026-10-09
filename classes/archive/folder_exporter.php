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
 * Export a folder as a standalone HTML tree with original file contents.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class folder_exporter {
    /**
     * Copy content and introduction assets, then write the folder's HTML page.
     *
     * @param \cm_info $cm Folder course module.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Archive workspace.
     * @return array Index list item and relative HTML filename to include in the ZIP.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'folder') {
            throw new \coding_exception('The folder exporter requires a folder resource');
        }
        $folder = $DB->get_record('folder', ['id' => $cm->instance], 'id, intro, introformat', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $intro = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $folder->intro, $context, 'mod_folder', 'intro', 0, $directory, (int)$cm->id
        );
        $intro = format_text($intro, $folder->introformat, ['context' => $context, 'filter' => false]);
        \local_flatarchive_copy_pluginfiles_and_rewrite(
            '', $context, 'mod_folder', 'content', 0, $directory, (int)$cm->id
        );
        $files = get_file_storage()->get_area_files($context->id, 'mod_folder', 'content', 0, 'filepath, filename', true);
        $tree = ['directories' => [], 'files' => []];
        foreach ($files as $file) {
            $node =& $tree;
            foreach (explode('/', trim($file->get_filepath(), '/')) as $part) {
                if ($part === '') {
                    continue;
                }
                if (!isset($node['directories'][$part])) {
                    $node['directories'][$part] = ['directories' => [], 'files' => []];
                }
                $node =& $node['directories'][$part];
            }
            if (!$file->is_directory()) {
                $path = paths::assets((int)$cm->id, 'content', 0) . $file->get_filepath() . $file->get_filename();
                $node['files'][$file->get_filename()] = implode('/', array_map('rawurlencode', explode('/', $path)));
            }
            unset($node);
        }
        $content = $intro . \html_writer::tag('h2', get_string('foldercontents', 'local_flatarchive'));
        $content .= self::render_tree($tree);
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        // Keep generated HTML outside the original content tree to avoid index.html collisions.
        $filename = 'folder-' . $cm->id . '.html';
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $content, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        $link = \html_writer::link($filename, s($cm->name));
        return [
            'item' => \html_writer::tag('li', $link . ($badges ? ' ' . $badges : ''), ['id' => 'module-' . $cm->id]),
            'filename' => $filename,
        ];
    }

    /**
     * Render nested lists without JavaScript, with directories before files.
     *
     * @param array $tree Directory and file entries.
     * @return string Offline HTML tree, including explicit empty folders.
     */
    private static function render_tree(array $tree): string {
        if (!$tree['directories'] && !$tree['files']) {
            return \html_writer::tag('p', get_string('folderempty', 'local_flatarchive'));
        }
        ksort($tree['directories'], SORT_NATURAL | SORT_FLAG_CASE);
        ksort($tree['files'], SORT_NATURAL | SORT_FLAG_CASE);
        $items = '';
        foreach ($tree['directories'] as $name => $child) {
            $items .= \html_writer::tag('li', \html_writer::tag('strong', s((string)$name)) . self::render_tree($child));
        }
        foreach ($tree['files'] as $name => $url) {
            $items .= \html_writer::tag('li', \html_writer::link($url, s((string)$name)));
        }
        return \html_writer::tag('ul', $items, ['class' => 'fa-folder-tree']);
    }
}
