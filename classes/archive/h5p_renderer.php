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
 * Static, non-executable projections of supported H5P content structures.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class h5p_renderer {
    /** @var h5p_assets Package assets. */
    private h5p_assets $package;

    /**
     * Create a renderer for one package.
     *
     * @param h5p_assets $package Validated data.
     */
    public function __construct(h5p_assets $package) {
        $this->package = $package;
    }

    /**
     * Render a supported library, or explicitly identify an unsupported element.
     *
     * @param string $library Library and optional version.
     * @param array $params Parameters.
     * @param int $depth Recursion guard.
     * @return string Static HTML.
     */
    public function render(string $library, array $params, int $depth = 0): string {
        $parts = explode(' ', $library);
        if ($depth > 16 || (isset($parts[1]) && (int)$parts[1] !== 1)) {
            return $this->notice('h5punsupported', $library);
        }
        $type = $parts[0];
        $html = '';
        if (!empty($params['media']['type'])) {
            $html .= $this->child($params['media']['type'], $depth);
        }
        switch ($type) {
            case 'H5P.AdvancedText':
            case 'H5P.Text':
            case 'H5P.Table':
                $html .= $this->rich($params['text'] ?? $params['table'] ?? '');
                break;
            case 'H5P.Image':
                $html .= $this->media($params['file'] ?? [], 'image', $params['alt'] ?? '');
                $html .= $this->rich($params['title'] ?? '');
                break;
            case 'H5P.Video':
            case 'H5P.Audio':
                $html .= $this->sources($params['sources'] ?? $params['files'] ?? [], $type === 'H5P.Video' ? 'video' : 'audio');
                break;
            case 'H5P.Link':
                $html .= $this->link_widget($params['linkWidget'] ?? ['url' => $params['url'] ?? ''], $params['title'] ?? '');
                break;
            case 'H5P.Nil':
                break;
            case 'H5P.DragText':
                $html .= $this->rich($params['taskDescription'] ?? '');
                $html .= $this->blanks([$params['textField'] ?? '']);
                if (!empty($params['distractors'])) {
                    $html .= $this->heading('h5pdistractors') . $this->rich($params['distractors']);
                }
                break;
            case 'H5P.Blanks':
                $html .= $this->rich($params['text'] ?? '');
                $html .= $this->blanks($params['questions'] ?? []);
                break;
            case 'H5P.MultiChoice':
                $html .= $this->rich($params['question'] ?? '');
                $choices = [];
                $solutions = [];
                foreach ($params['answers'] ?? [] as $i => $answer) {
                    $choices[] = $this->rich($answer['text'] ?? '');
                    if (!empty($answer['correct'])) {
                        $solutions[] = $this->rich($answer['text'] ?? '');
                    }
                    $html .= $this->feedback($answer['tipsAndFeedback'] ?? [], (string)($i + 1));
                }
                $html .= $this->heading('h5pchoices') . \html_writer::alist($choices, [], 'ol');
                $html .= $this->heading('h5psolutions') . \html_writer::alist($solutions);
                break;
            case 'H5P.TrueFalse':
                $html .= $this->rich($params['question'] ?? '');
                $html .= $this->heading('h5psolutions');
                $html .= s(get_string(($params['correct'] ?? '') === 'true' ? 'h5ptrue' : 'h5pfalse', 'local_flatarchive'));
                $html .= $this->feedback($params['behaviour'] ?? []);
                break;
            case 'H5P.SingleChoiceSet':
            case 'H5P.Summary':
                $html .= $this->rich($params['intro'] ?? '');
                foreach ($params['choices'] ?? $params['summaries'] ?? [] as $question) {
                    $html .= $this->rich($question['question'] ?? '');
                    $answers = $question['answers'] ?? $question['summary'] ?? [];
                    $html .= $this->heading('h5pchoices');
                    $html .= \html_writer::alist(array_map([$this, 'rich'], $answers), [], 'ol');
                    $html .= $this->heading('h5psolutions') . $this->rich($answers[0] ?? '');
                    // Older Summary packages store a plain string; newer schemas use a tip group.
                    $tip = $question['tip'] ?? [];
                    if (is_string($tip)) {
                        $tip = ['tip' => $tip];
                    }
                    $html .= $this->feedback(is_array($tip) ? $tip : []);
                }
                break;
            case 'H5P.DragQuestion':
                $html .= $this->drag_question($params, $depth);
                break;
            case 'H5P.QuestionSet':
                $html .= $this->rich($params['introPage']['introduction'] ?? '');
                foreach ($params['questions'] ?? [] as $question) {
                    $html .= $this->child($question, $depth);
                }
                $html .= $this->ending($params['endGame'] ?? []);
                break;
            case 'H5P.CoursePresentation':
                foreach ($params['presentation']['slides'] ?? [] as $slide) {
                    $html .= $this->slide($slide, $depth);
                }
                break;
            case 'H5P.InteractiveVideo':
                $html .= $this->interactive_video($params, $depth);
                break;
            default:
                $html .= $this->notice('h5punsupported', $library);
        }
        $html .= $this->feedback(['overallFeedback' => $params['overallFeedback'] ?? []]);
        return \html_writer::div($html, 'flatarchive-h5p-element');
    }

    /**
     * Render a nested library instance, including its title and attribution.
     *
     * @param array $child Library instance.
     * @param int $depth Current nesting depth.
     * @return string HTML.
     */
    public function child(array $child, int $depth = 0): string {
        $title = $child['metadata']['title'] ?? '';
        $html = $title !== '' ? \html_writer::tag('h3', s($title)) : '';
        $html .= $this->render($child['library'] ?? 'Unknown', $child['params'] ?? [], $depth + 1);
        return $html . $this->credits($child['metadata'] ?? []);
    }

    /**
     * Clean authored markup and resolve only known local media or explicit web links.
     *
     * @param string $html Authored HTML.
     * @return string Passive HTML.
     */
    public function rich(string $html): string {
        if ($html === '') {
            return '';
        }
        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . clean_text($html, FORMAT_HTML) . '</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $allowed = ['html', 'body', 'div', 'p', 'span', 'strong', 'em', 'b', 'i', 'u', 's', 'sub', 'sup',
            'br', 'hr', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code',
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'caption', 'a', 'img', 'figure', 'figcaption'];
        $nodes = iterator_to_array($doc->getElementsByTagName('*'));
        $notice = '';
        foreach ($nodes as $node) {
            if (!in_array($node->tagName, $allowed, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if (!in_array($name, ['href', 'src', 'alt', 'title', 'colspan', 'rowspan'], true)) {
                    $node->removeAttribute($attribute->name);
                }
            }
            if ($node->hasAttribute('src')) {
                $source = $node->getAttribute('src');
                $url = $this->package->asset($source);
                if ($url === null) {
                    $notice .= $this->notice('h5pmediaunavailable', $source);
                    $node->removeAttribute('src');
                } else {
                    $node->setAttribute('src', $url);
                }
            }
            if ($node->hasAttribute('href')) {
                $href = $node->getAttribute('href');
                $asset = $this->package->asset($href);
                if ($asset !== null) {
                    $node->setAttribute('href', $asset);
                } else if (!preg_match('~^https?://~i', $href)) {
                    $node->removeAttribute('href');
                }
            }
        }
        $body = $doc->getElementsByTagName('body')->item(0);
        $result = '';
        foreach ($body->childNodes as $node) {
            $result .= $doc->saveHTML($node);
        }
        return $result . $notice;
    }

    /**
     * Render one passive media file, with an explicit external dependency fallback.
     *
     * @param array $file File object.
     * @param string $kind image, audio or video.
     * @param string $alt Image alternative.
     * @param array $tracks Video text tracks.
     * @return string HTML.
     */
    public function media(array $file, string $kind, string $alt = '', array $tracks = []): string {
        $path = $file['path'] ?? '';
        if ($path === '') {
            return '';
        }
        $url = $this->package->asset($path);
        if ($url === null) {
            return $this->notice('h5pmediaunavailable', $path) . $this->external($path, $path);
        }
        if ($kind === 'image') {
            $html = \html_writer::empty_tag('img', ['src' => $url, 'alt' => $alt, 'class' => 'flatarchive-h5p-image']);
        } else {
            $children = \html_writer::link($url, s(basename($path)));
            foreach ($tracks as $track) {
                $trackurl = $this->package->asset($track['track']['path'] ?? '');
                $trackkind = $track['kind'] ?? 'subtitles';
                if ($trackurl && in_array($trackkind, ['subtitles', 'captions', 'descriptions'], true)) {
                    $children .= \html_writer::empty_tag('track', [
                        'src' => $trackurl, 'kind' => $trackkind,
                        'srclang' => $track['srcLang'] ?? '', 'label' => $track['label'] ?? '',
                    ]);
                }
            }
            $html = \html_writer::tag($kind, $children, [
                'src' => $url, 'controls' => 'controls', 'preload' => 'metadata',
            ]);
        }
        return $html . $this->credits($file['copyright'] ?? []);
    }

    /**
     * Render available audio/video encodings as independent native players.
     *
     * @param array $files Sources.
     * @param string $kind Media kind.
     * @param array $tracks Video text tracks.
     * @return string HTML.
     */
    public function sources(array $files, string $kind, array $tracks = []): string {
        $html = '';
        foreach ($files as $file) {
            $html .= $this->media($file, $kind, '', $tracks);
        }
        return $html;
    }

    /**
     * Render blank text, word bank, corrected text and per-blank guidance.
     *
     * @param array $texts Marked texts.
     * @return string HTML.
     */
    private function blanks(array $texts): string {
        $html = '';
        foreach ($texts as $text) {
            $words = [];
            $notes = '';
            $corrected = preg_replace_callback('/\*([^*]+)\*/u', function ($match) use (&$words, &$notes) {
                $word = $match[1];
                $fields = ['tip' => '/:([^\\\\*]+)/u', 'feedbackOnCorrect' => '/\\\\\+([^\\\\*:]+)/u',
                    'feedbackOnWrong' => '/\\\\-([^\\\\*:]+)/u'];
                $feedback = [];
                foreach ($fields as $key => $pattern) {
                    if (preg_match($pattern, $word, $found)) {
                        $feedback[$key] = $found[1];
                        $word = str_replace($found[0], '', $word);
                    }
                }
                $words[] = trim($word);
                $notes .= $this->feedback($feedback, (string)count($words));
                return '<strong>' . s(trim($word)) . '</strong>';
            }, $text);
            $html .= $this->rich(preg_replace('/\*([^*]+)\*/u', ' [____] ', $text));
            $html .= $this->heading('h5pwords') . \html_writer::alist(array_map('s', $words));
            $html .= $this->heading('h5psolutions') . $this->rich($corrected) . $notes;
        }
        return $html;
    }

    /**
     * Preserve fixed drop zones and explicit correct element-to-zone mappings.
     *
     * @param array $params Content parameters.
     * @param int $depth Current depth.
     * @return string HTML.
     */
    private function drag_question(array $params, int $depth): string {
        $question = $params['question'] ?? [];
        $html = $this->rich($params['taskDescription'] ?? '');
        $background = $this->media($question['settings']['background'] ?? [], 'image');
        $zones = $question['task']['dropZones'] ?? [];
        $elements = $question['task']['elements'] ?? [];
        $overlay = '';
        foreach ($zones as $i => $zone) {
            $style = 'left:' . self::percent($zone['x'] ?? 0) . '%;top:' . self::percent($zone['y'] ?? 0) . '%;';
            $style .= 'width:' . self::percent($zone['width'] ?? 10) . '%;height:' . self::percent($zone['height'] ?? 10) . '%;';
            $overlay .= \html_writer::tag('span', (string)($i + 1), ['class' => 'flatarchive-h5p-zone', 'style' => $style]);
        }
        $width = max(1, (float)($question['settings']['size']['width'] ?? 620));
        $height = max(1, (float)($question['settings']['size']['height'] ?? 310));
        $html .= \html_writer::div($background . $overlay, 'flatarchive-h5p-board', [
            'style' => 'aspect-ratio:' . $width . '/' . $height . ';',
        ]);
        $html .= $this->heading('h5pelements');
        foreach ($elements as $i => $element) {
            $html .= \html_writer::tag('h4', (string)($i + 1)) . $this->child($element['type'] ?? [], $depth);
        }
        $html .= $this->heading('h5psolutions');
        foreach ($zones as $i => $zone) {
            $html .= \html_writer::tag('h4', get_string('h5pzone', 'local_flatarchive', $i + 1));
            $html .= $this->rich($zone['label'] ?? '');
            $correct = [];
            foreach ($zone['correctElements'] ?? [] as $id) {
                if (isset($elements[$id])) {
                    $correct[] = (string)((int)$id + 1) . ': ' . $this->child($elements[$id]['type'] ?? [], $depth);
                }
            }
            $html .= \html_writer::alist($correct) . $this->feedback($zone['tipsAndFeedback'] ?? []);
        }
        return $html;
    }

    /**
     * Clamp numeric authoring coordinates before placing them in CSS.
     *
     * @param mixed $value Coordinate.
     * @return float Safe percentage.
     */
    private static function percent($value): float {
        return max(0, min(100, (float)$value));
    }

    /**
     * Render slide elements in authored order, with explicit navigation instructions.
     *
     * @param array $slide Slide parameters.
     * @param int $depth Nesting depth.
     * @param array $links Optional page links for real slide navigation.
     * @return string HTML.
     */
    public function slide(array $slide, int $depth = 0, array $links = []): string {
        $html = $this->media($slide['slideBackgroundSelector']['imageSlideBackground'] ?? [], 'image');
        foreach ($slide['elements'] ?? [] as $element) {
            $html .= $this->child($element['action'] ?? [], $depth);
            if (!empty($element['solution'])) {
                $html .= $this->heading('h5psolutions') . $this->rich($element['solution']);
            }
            if (isset($element['goToSlideType'])) {
                $target = $element['goToSlideType'] === 'specified' ? (string)($element['goToSlide'] ?? '')
                    : $element['goToSlideType'];
                $label = get_string('h5pslidelink', 'local_flatarchive', $target);
                $html .= isset($links[$target]) ? \html_writer::link($links[$target], s($label))
                    : \html_writer::tag('p', s($label));
            }
        }
        return $html;
    }

    /**
     * Export video media and a chronological transcript of interactions.
     *
     * @param array $params Content parameters.
     * @param int $depth Nesting depth.
     * @return string HTML.
     */
    private function interactive_video(array $params, int $depth): string {
        $video = $params['interactiveVideo'] ?? [];
        $start = $video['video']['startScreenOptions'] ?? [];
        $html = $this->rich($start['title'] ?? '') . $this->rich($start['shortStartDescription'] ?? '');
        $html .= $this->media($start['poster'] ?? [], 'image');
        $html .= $this->sources($video['video']['files'] ?? [], 'video', $video['video']['textTracks']['videoTrack'] ?? []);
        foreach ($video['video']['textTracks']['videoTrack'] ?? [] as $track) {
            $path = $track['track']['path'] ?? '';
            $url = $this->package->asset($path);
            $html .= $url ? \html_writer::link($url, s($track['label'] ?? $path)) : $this->notice('h5pmediaunavailable', $path);
        }
        $interactions = $video['assets']['interactions'] ?? [];
        usort($interactions, fn($a, $b) => ($a['duration']['from'] ?? 0) <=> ($b['duration']['from'] ?? 0));
        foreach ($interactions as $interaction) {
            $time = self::timestamp($interaction['duration']['from'] ?? 0) . ' – ';
            $time .= self::timestamp($interaction['duration']['to'] ?? 0);
            $html .= \html_writer::tag('h3', s($time . ' ' . ($interaction['label'] ?? '')));
            $html .= $this->child($interaction['action'] ?? [], $depth);
            foreach (['correct', 'wrong'] as $condition) {
                $branch = $interaction['adaptivity'][$condition] ?? [];
                if (isset($branch['seekTo']) && $branch['seekTo'] >= 0) {
                    $label = get_string('h5pjump' . $condition, 'local_flatarchive', self::timestamp($branch['seekTo']));
                    $html .= \html_writer::tag('p', s($label)) . $this->rich($branch['message'] ?? '');
                }
            }
            if (!empty($interaction['adaptivity']['requireCompletion'])) {
                $html .= $this->notice('h5prequirecompletion');
            }
            if (($interaction['goto']['type'] ?? '') === 'timecode') {
                $html .= $this->notice('h5pjump', self::timestamp($interaction['goto']['time'] ?? 0));
            } else if (($interaction['goto']['type'] ?? '') === 'url') {
                $goto = $interaction['goto']['url'] ?? [];
                $html .= $this->link_widget($goto, $goto['url'] ?? '');
            }
        }
        foreach ($video['assets']['bookmarks'] ?? [] as $bookmark) {
            $html .= \html_writer::tag('p', s(self::timestamp($bookmark['time']) . ' — ' . ($bookmark['label'] ?? '')));
        }
        if (!empty($video['summary']['task'])) {
            $html .= $this->child($video['summary']['task'], $depth);
        }
        return $html;
    }

    /**
     * Format a media timestamp without timezone conversion.
     *
     * @param mixed $seconds Seconds.
     * @return string Timestamp.
     */
    private static function timestamp($seconds): string {
        $seconds = max(0, (int)$seconds);
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    /**
     * Retain feedback without executing evaluation or displaying a new score.
     *
     * @param array $data Feedback fields.
     * @param string $prefix Optional answer number.
     * @return string HTML.
     */
    public function feedback(array $data, string $prefix = ''): string {
        $html = '';
        $keys = ['tip', 'chosenFeedback', 'notChosenFeedback', 'feedbackOnCorrect', 'feedbackOnIncorrect', 'feedbackOnWrong'];
        foreach ($keys as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                $html .= \html_writer::tag('h4', s($prefix . ' ' . get_string('h5p' . strtolower($key), 'local_flatarchive')));
                $html .= $this->rich($data[$key]);
            }
        }
        foreach ($data['overallFeedback']['overallFeedback'] ?? [] as $range) {
            $html .= \html_writer::tag('h4', s(($range['from'] ?? 0) . '% – ' . ($range['to'] ?? 100) . '%'));
            $html .= $this->rich($range['feedback'] ?? '');
        }
        return $html;
    }

    /**
     * Preserve outcome feedback and optional success/failure media.
     *
     * @param array $ending End-of-question-set data.
     * @return string HTML.
     */
    public function ending(array $ending): string {
        $html = $this->feedback($ending);
        foreach (['successGreeting', 'successComment', 'failGreeting', 'failComment'] as $key) {
            $html .= $this->rich($ending['oldFeedback'][$key] ?? '');
        }
        foreach (['successVideo' => 'h5pfeedbackoncorrect', 'failVideo' => 'h5pfeedbackonwrong'] as $key => $label) {
            if (!empty($ending[$key])) {
                $html .= $this->heading($label) . $this->sources($ending[$key], 'video');
            }
        }
        return $html;
    }

    /**
     * Resolve H5P link widgets, whose protocol and address are stored separately.
     *
     * @param array $widget Link parameters.
     * @param string $label Link label.
     * @return string HTML link or explicit unavailable-link notice.
     */
    private function link_widget(array $widget, string $label): string {
        global $CFG;
        $url = $widget['url'] ?? '';
        $protocol = $widget['protocol'] ?? 'https://';
        if (!preg_match('~^https?://~i', $url)) {
            if (in_array($protocol, ['http://', 'https://'], true)) {
                $url = $protocol . $url;
            } else if ($protocol === '/') {
                $url = $CFG->wwwroot . '/' . ltrim($url, '/');
            }
        }
        return $this->external($url, $label) ?: $this->notice('h5punresolvedlink', $label . ' — ' . $url);
    }

    /**
     * Keep attribution as escaped documentary text.
     *
     * @param array $metadata H5P metadata or copyright object.
     * @return string HTML.
     */
    public function credits(array $metadata): string {
        $lines = [];
        foreach (['title', 'author', 'license', 'licenseVersion', 'yearFrom', 'yearTo', 'source', 'licenseExtras'] as $key) {
            if (isset($metadata[$key]) && is_scalar($metadata[$key])) {
                $lines[] = s($key . ': ' . $metadata[$key]);
            }
        }
        foreach ($metadata['authors'] ?? [] as $author) {
            $lines[] = s(($author['name'] ?? '') . ' — ' . ($author['role'] ?? ''));
        }
        return $lines ? $this->heading('h5pcredits') . \html_writer::alist($lines) : '';
    }

    /**
     * Safe web link, with no network access during export.
     *
     * @param string $url Candidate URL.
     * @param string $label Label.
     * @return string HTML or empty string.
     */
    private function external(string $url, string $label): string {
        return preg_match('~^https?://~i', $url) ? \html_writer::link($url, s($label ?: $url)) : '';
    }

    /**
     * Translated heading.
     *
     * @param string $key Language key.
     * @return string HTML.
     */
    private function heading(string $key): string {
        return \html_writer::tag('h3', get_string($key, 'local_flatarchive'));
    }

    /**
     * Explicit limitation or dependency note.
     *
     * @param string $key Language key.
     * @param string $value Optional detail.
     * @return string HTML.
     */
    public function notice(string $key, string $value = ''): string {
        return \html_writer::div(s(get_string($key, 'local_flatarchive', $value)), 'flatarchive-h5p-notice');
    }
}
