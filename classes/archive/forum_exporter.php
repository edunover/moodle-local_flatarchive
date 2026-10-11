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
 * Export readable forum discussions and messages without changing their read state.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class forum_exporter {
    /**
     * Create a forum index and a static page for each readable discussion.
     *
     * @param \cm_info $cm Forum module.
     * @param \course_modinfo $modinfo Course structure.
     * @param string $directory Archive directory.
     * @return array Generated files and course index item.
     */
    public static function render(\cm_info $cm, \course_modinfo $modinfo, string $directory): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/forum/lib.php');
        require_once($CFG->dirroot . '/local/flatarchive/locallib.php');
        require_once($CFG->dirroot . '/local/flatarchive/archive_templates/page_template.php');
        if ($cm->modname !== 'forum') {
            throw new \coding_exception('The forum exporter requires a forum activity');
        }
        $context = \context_module::instance($cm->id);
        require_capability('mod/forum:viewdiscussion', $context);
        if (!$cm->uservisible) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $forum = $DB->get_record('forum', ['id' => $cm->instance], '*', MUST_EXIST);
        $filename = 'forum-' . $cm->id . '.html';
        $filenames = [$filename];
        $badges = \local_flatarchive_visibility_badges($cm, $modinfo);
        $intro = self::text($forum->intro, $forum->introformat, 'intro', 0, $cm, $directory);
        $intro .= \html_writer::tag('p', get_string('forumscope', 'local_flatarchive'));
        $links = [];
        $discussions = $DB->get_records('forum_discussions', ['forum' => $forum->id], 'pinned DESC, timemodified DESC, id');
        foreach ($discussions as $discussion) {
            if (!forum_user_can_see_discussion($forum, $discussion, $context)) {
                continue;
            }
            $posts = [];
            foreach ($DB->get_records('forum_posts', ['discussion' => $discussion->id], 'created, id') as $post) {
                if (forum_user_can_see_post($forum, $discussion, $post, null, $cm)) {
                    $posts[$post->id] = $post;
                }
            }
            if (!$posts) {
                continue;
            }
            $discussionfile = 'forum-' . $cm->id . '-discussion-' . $discussion->id . '.html';
            $body = \html_writer::tag('p', \html_writer::link($filename, get_string('backtoforum', 'local_flatarchive')));
            foreach ($posts as $post) {
                $message = \html_writer::tag('h2', s($post->subject));
                if (!forum_is_author_hidden($post, $forum)) {
                    $fields = 'id, deleted, ' . implode(', ', \core_user\fields::get_name_fields());
                    $author = $DB->get_record('user', ['id' => $post->userid, 'deleted' => 0], $fields);
                    $name = $author ? fullname($author, has_capability('moodle/site:viewfullnames', $context)) :
                        get_string('forumunknownauthor', 'local_flatarchive');
                    $message .= \html_writer::tag('p', s($name));
                }
                $message .= \html_writer::tag('time', s(userdate($post->created)), [
                    'datetime' => gmdate('Y-m-d\TH:i:s\Z', $post->created),
                ]);
                if ($post->modified > $post->created) {
                    $message .= \html_writer::tag('p', s(get_string(
                        'forummodified',
                        'local_flatarchive',
                        userdate($post->modified)
                    )));
                }
                if (!empty($post->privatereplyto)) {
                    $message .= \html_writer::tag('p', get_string('forumprivate', 'local_flatarchive'));
                }
                if (isset($posts[$post->parent])) {
                    $message .= \html_writer::tag('p', \html_writer::link(
                        '#post-' . $post->parent,
                        get_string('forumparent', 'local_flatarchive')
                    ));
                }
                $message .= self::text($post->message, $post->messageformat, 'post', $post->id, $cm, $directory);
                // Copy attachments only after the native post visibility check has succeeded.
                self::text('', FORMAT_HTML, 'attachment', $post->id, $cm, $directory);
                $attachments = [];
                $files = get_file_storage()->get_area_files(
                    $context->id,
                    'mod_forum',
                    'attachment',
                    $post->id,
                    'filepath, filename',
                    false
                );
                foreach ($files as $file) {
                    $path = paths::assets($cm->id, 'attachment', $post->id)
                        . $file->get_filepath() . $file->get_filename();
                    $url = implode('/', array_map('rawurlencode', explode('/', $path)));
                    $attachments[] = \html_writer::link($url, s($file->get_filename()));
                }
                if ($attachments) {
                    $message .= \html_writer::tag('h3', get_string('forumattachments', 'local_flatarchive'));
                    $message .= \html_writer::alist($attachments);
                }
                $body .= \html_writer::tag('article', $message, ['id' => 'post-' . $post->id]);
            }
            $html = \local_flatarchive_render_page_template(
                $modinfo->get_course(),
                $cm,
                $discussion->name,
                $body,
                '',
                $badges
            );
            \local_flatarchive_write_file($directory . '/' . $discussionfile, $html);
            $filenames[] = $discussionfile;
            $links[] = \html_writer::link($discussionfile, s($discussion->name));
        }
        $intro .= $links ? \html_writer::alist($links) :
            \html_writer::tag('p', get_string('forumempty', 'local_flatarchive'));
        $html = \local_flatarchive_render_page_template($modinfo->get_course(), $cm, $cm->name, $intro, '', $badges);
        \local_flatarchive_write_file($directory . '/' . $filename, $html);
        return ['filename' => $filename, 'filenames' => $filenames,
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name)) . $badges)];
    }

    /**
     * Preserve local files and format HTML without running live filters.
     *
     * @param string $text Source text.
     * @param int $format Text format.
     * @param string $area File area.
     * @param int $itemid File item ID.
     * @param \cm_info $cm Forum module.
     * @param string $directory Archive directory.
     * @return string Offline content.
     */
    private static function text(
        string $text,
        int $format,
        string $area,
        int $itemid,
        \cm_info $cm,
        string $directory
    ): string {
        $context = \context_module::instance($cm->id);
        $text = \local_flatarchive_copy_pluginfiles_and_rewrite(
            $text,
            $context,
            'mod_forum',
            $area,
            $itemid,
            $directory,
            (int)$cm->id
        );
        return format_text($text, $format, ['context' => $context, 'filter' => false]);
    }
}
