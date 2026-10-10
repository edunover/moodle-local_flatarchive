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
 * Export readable wiki spaces and their current revisions without changing Moodle caches.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class wiki_exporter {
    /**
     * Write a wiki index and one HTML document per readable page.
     *
     * @param \cm_info $cm Wiki module.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Archive directory.
     * @return array Index item, index filename and all HTML filenames.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/wiki/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'wiki') {
            throw new \coding_exception('The wiki exporter requires a wiki');
        }
        $context = \context_module::instance($cm->id);
        require_capability('mod/wiki:viewpage', $context);
        if (!$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $wiki = $DB->get_record('wiki', ['id' => $cm->instance], '*', MUST_EXIST);
        $spaces = [];
        $pages = [];
        $map = [];
        foreach ($DB->get_records('wiki_subwikis', ['wikiid' => $wiki->id], 'id') as $space) {
            if (!wiki_user_can_view($space, $wiki)) {
                continue;
            }
            $spaces[$space->id] = $space;
            foreach ($DB->get_records('wiki_pages', ['subwikiid' => $space->id], 'title, id') as $page) {
                $version = wiki_get_current_version($page->id);
                if (!$version) {
                    continue;
                }
                $page->currentversion = $version;
                $pages[$page->id] = $page;
                $map[$page->id] = 'wiki-' . $cm->id . '-page-' . $page->id . '.html';
            }
        }
        $filename = 'wiki-' . $cm->id . '.html';
        $filenames = [$filename];
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        $intro = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $wiki->intro ?? '',
            $context,
            'mod_wiki',
            'intro',
            0,
            $directory,
            (int)$cm->id
        );
        $content = format_text($intro, $wiki->introformat, ['context' => $context, 'filter' => false]);
        $content .= \html_writer::tag('p', get_string('wikiscope', 'local_flatarchive'));
        foreach ($spaces as $space) {
            $label = get_string('wikispace', 'local_flatarchive', $space->id);
            if ($space->groupid) {
                $groupname = $DB->get_field('groups', 'name', ['id' => $space->groupid, 'courseid' => $cm->course]);
                $label .= ' — ' . ($groupname ?: get_string('wikiunknowngroup', 'local_flatarchive'));
            }
            if ($space->userid) {
                $label .= ' — ' . self::author((int)$space->userid);
            }
            $content .= \html_writer::tag('h2', s($label), ['id' => 'subwiki-' . $space->id]);
            $items = '';
            foreach ($pages as $page) {
                if ((int)$page->subwikiid !== (int)$space->id) {
                    continue;
                }
                $link = \html_writer::link($map[$page->id], s($page->title));
                if ($page->title === $wiki->firstpagetitle) {
                    $link .= ' — ' . get_string('wikifirstpage', 'local_flatarchive');
                }
                $items .= \html_writer::tag('li', $link);
            }
            $content .= $items ? \html_writer::tag('ul', $items) :
                \html_writer::tag('p', get_string('wikiempty', 'local_flatarchive'));
            // Wiki files belong to a subwiki, not a particular page.
            \local_flatarchive_copy_pluginfiles_and_rewrite(
                '',
                $context,
                'mod_wiki',
                'attachments',
                (int)$space->id,
                $directory,
                (int)$cm->id
            );
            $files = get_file_storage()->get_area_files(
                $context->id,
                'mod_wiki',
                'attachments',
                $space->id,
                'filepath, filename',
                false
            );
            $attachments = '';
            foreach ($files as $file) {
                $path = paths::assets((int)$cm->id, 'attachments', (int)$space->id);
                $path .= $file->get_filepath() . $file->get_filename();
                $url = implode('/', array_map('rawurlencode', explode('/', $path)));
                $attachments .= \html_writer::tag('li', \html_writer::link($url, s($file->get_filename())));
            }
            if ($attachments) {
                $content .= \html_writer::tag('h3', get_string('wikiattachments', 'local_flatarchive'));
                $content .= \html_writer::tag('ul', $attachments);
            }
        }
        if (!$spaces) {
            $content .= \html_writer::tag('p', get_string('wikiempty', 'local_flatarchive'));
        }
        foreach ($pages as $page) {
            $version = $page->currentversion;
            $body = \html_writer::tag('p', \html_writer::link(
                $filename . '#subwiki-' . $page->subwikiid,
                get_string('backtowiki', 'local_flatarchive')
            ));
            $body .= \html_writer::tag('p', get_string('wikilastauthor', 'local_flatarchive') . ': ' .
                s(self::author((int)$version->userid)) . ' — ' . \html_writer::tag(
                    'time',
                    s(userdate($version->timecreated)),
                    ['datetime' => gmdate('Y-m-d\TH:i:s\Z', $version->timecreated)]
                ));
            $parsed = wiki_parse_content($version->contentformat, $version->content, [
                'swid' => $page->subwikiid, 'pageid' => $page->id, 'pretty_print' => true,
            ]);
            $body .= format_text($parsed['toc'] . $parsed['parsed_text'], FORMAT_HTML, [
                'context' => $context, 'filter' => false, 'allowid' => true,
            ]);
            $body .= self::comments($page, $cm, $context);
            $body = self::rewrite($body, $page, $cm, $context, $spaces, $pages, $map);
            $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $page->title, $body, '', $badges);
            \local_flatarchive_write_file($directory . '/' . $map[$page->id], $html);
            $filenames[] = $map[$page->id];
        }
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $content, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        return ['filename' => $filename, 'filenames' => $filenames,
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name)) . $badges)];
    }

    /**
     * Resolve names without including profile fields or deleted identities.
     *
     * @param int $id User ID.
     * @return string Display name.
     */
    private static function author(int $id): string {
        global $DB;
        $user = $DB->get_record('user', ['id' => $id], 'id, deleted, ' . implode(', ', \core_user\fields::get_name_fields()));
        return !$user || $user->deleted ? get_string('wikiunknownauthor', 'local_flatarchive') : fullname($user);
    }

    /**
     * Read comments only after page visibility has been checked.
     *
     * @param \stdClass $page Wiki page.
     * @param \cm_info $cm Module.
     * @param \context_module $context Module context.
     * @return string Static comments.
     */
    private static function comments(\stdClass $page, \cm_info $cm, \context_module $context): string {
        global $CFG, $DB;
        if (empty($CFG->usecomments) || !has_capability('mod/wiki:viewcomment', $context)) {
            return '';
        }
        $items = '';
        $records = $DB->get_recordset_select(
            'comments',
            'contextid = :ctx AND commentarea = :area AND itemid = :page AND ' .
            '(component = :component OR component IS NULL)',
            ['ctx' => $context->id, 'area' => 'wiki_page', 'page' => $page->id, 'component' => 'mod_wiki'],
            'timecreated, id'
        );
        try {
            foreach ($records as $comment) {
                if (in_array((int)$comment->format, [(int)FORMAT_CREOLE, (int)FORMAT_NWIKI], true)) {
                    $format = (int)$comment->format === (int)FORMAT_CREOLE ? 'creole' : 'nwiki';
                    $parsed = wiki_parse_content($format, $comment->content, [
                        'swid' => $page->subwikiid, 'pageid' => $page->id, 'pretty_print' => true,
                    ]);
                    $text = format_text($parsed['parsed_text'], FORMAT_HTML, ['context' => $context, 'filter' => false]);
                } else {
                    $text = format_text($comment->content, $comment->format, ['context' => $context, 'filter' => false]);
                }
                $date = \html_writer::tag('time', s(userdate($comment->timecreated)), [
                    'datetime' => gmdate('Y-m-d\TH:i:s\Z', $comment->timecreated),
                ]);
                $items .= \html_writer::tag('li', \html_writer::tag('p', s(self::author((int)$comment->userid)) .
                    ' — ' . $date) . $text);
            }
        } finally {
            $records->close();
        }
        return $items ? \html_writer::tag('h2', get_string('wikicomments', 'local_flatarchive')) .
            \html_writer::tag('ol', $items) : '';
    }

    /**
     * Rewrite wiki navigation and files using only authorised export targets.
     *
     * @param string $html Formatted page HTML.
     * @param \stdClass $page Current wiki page.
     * @param \cm_info $cm Module.
     * @param \context_module $context Module context.
     * @param array $spaces Exported subwikis.
     * @param array $pages Exported pages.
     * @param array $map Page ID to archive filename.
     * @return string Portable HTML fragment.
     */
    private static function rewrite(
        string $html,
        \stdClass $page,
        \cm_info $cm,
        \context_module $context,
        array $spaces,
        array $pages,
        array $map
    ): string {
        global $CFG;
        $html = str_replace('@@PLUGINFILE@@/', paths::assets((int)$cm->id, 'attachments', (int)$page->subwikiid) . '/', $html);
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $basepath = rtrim(parse_url($CFG->wwwroot, PHP_URL_PATH) ?? '', '/');
        foreach ((new \DOMXPath($dom))->query('//*[@href or @src or @poster or @data]') as $node) {
            foreach (['href', 'src', 'poster', 'data'] as $attribute) {
                if (!$node->hasAttribute($attribute)) {
                    continue;
                }
                $url = $node->getAttribute($attribute);
                if ($url === '' || $url[0] === '#') {
                    continue;
                }
                $parts = parse_url($url);
                if ($parts === false) {
                    continue;
                }
                // Never reinterpret another site's links as local wiki pages.
                if (isset($parts['host'])) {
                    $origin = parse_url($CFG->wwwroot);
                    if (
                        strcasecmp($parts['host'], $origin['host']) !== 0 ||
                        ($parts['port'] ?? null) !== ($origin['port'] ?? null)
                    ) {
                        continue;
                    }
                } else if (isset($parts['scheme'])) {
                    continue;
                }
                $path = $parts['path'] ?? '';
                parse_str($parts['query'] ?? '', $query);
                $file = $path === $basepath . '/pluginfile.php' ? ($query['file'] ?? '') :
                    (str_starts_with($path, $basepath . '/pluginfile.php/') ?
                        substr($path, strlen($basepath . '/pluginfile.php')) : '');
                $prefix = '/' . $context->id . '/mod_wiki/attachments/';
                if (str_starts_with($file, $prefix)) {
                    $relative = explode('/', substr($file, strlen($prefix)), 2);
                    $swid = (int)$relative[0];
                    if (isset($spaces[$swid], $relative[1])) {
                        $target = paths::assets((int)$cm->id, 'attachments', $swid) . '/' . rawurldecode($relative[1]);
                        $node->setAttribute($attribute, implode('/', array_map('rawurlencode', explode('/', $target))));
                    } else {
                        $node->removeAttribute($attribute);
                    }
                    continue;
                }
                $wikidir = $basepath . '/mod/wiki/';
                $isview = $path === $wikidir . 'view.php' || $path === 'view.php';
                $iscreate = $path === $wikidir . 'create.php' || $path === 'create.php';
                if ($attribute !== 'href' || (!$isview && !$iscreate)) {
                    continue;
                }
                $target = null;
                if ($isview && isset($query['pageid'])) {
                    $target = $map[(int)$query['pageid']] ?? null;
                } else if ($isview && (int)($query['id'] ?? 0) === (int)$cm->id) {
                    $target = 'wiki-' . $cm->id . '.html';
                } else if (isset($query['title'])) {
                    $swid = (int)($query['swid'] ?? $page->subwikiid);
                    foreach ($pages as $candidate) {
                        if ((int)$candidate->subwikiid === $swid && $candidate->title === $query['title']) {
                            $target = $map[$candidate->id];
                            break;
                        }
                    }
                }
                if ($target !== null) {
                    $node->setAttribute('href', $target . (isset($parts['fragment']) ? '#' . $parts['fragment'] : ''));
                } else {
                    $node->removeAttribute('href');
                    $node->setAttribute('title', get_string('wikinotarchived', 'local_flatarchive'));
                    $node->appendChild($dom->createTextNode(' (' . get_string('wikinotarchived', 'local_flatarchive') . ')'));
                }
            }
        }
        $result = '';
        foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $result .= $dom->saveHTML($node);
        }
        return $result;
    }
}
