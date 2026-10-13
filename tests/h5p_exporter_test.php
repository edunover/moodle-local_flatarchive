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

namespace local_flatarchive;

use local_flatarchive\archive\h5p_exporter;
use local_flatarchive\archive\h5p_package;
use local_flatarchive\archive\h5p_renderer;
use local_flatarchive\archive\layout;

/**
 * Static H5P content, passive files, navigation and Moodle report permissions.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_flatarchive\archive\h5p_package
 * @covers \local_flatarchive\archive\h5p_renderer
 * @covers \local_flatarchive\archive\h5p_exporter
 */
final class h5p_exporter_test extends \advanced_testcase {
    /**
     * Make an isolated Moodle module and replace its package with a controlled ZIP.
     *
     * @param string $library Main library.
     * @param array $params Content parameters.
     * @param array $assets Optional package entries.
     * @return array Course, activity, package file, archive directory.
     */
    private function fixture(string $library, array $params, array $assets = []): array {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $activity = $generator->create_module('h5pactivity', ['course' => $course->id]);
        $directory = make_temp_directory('flatarchive-h5p-test-' . random_string());
        $path = $directory . '/fixture.h5p';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);
        $zip->addFromString('h5p.json', json_encode([
            'title' => 'Preservation test', 'mainLibrary' => $library, 'license' => 'CC BY',
            'preloadedDependencies' => [['machineName' => $library, 'majorVersion' => 1, 'minorVersion' => 0]],
        ]));
        $zip->addFromString('content/content.json', json_encode($params));
        foreach ($assets as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $context = \context_module::instance($activity->cmid);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_h5pactivity', 'package');
        $file = $fs->create_file_from_pathname([
            'contextid' => $context->id, 'component' => 'mod_h5pactivity', 'filearea' => 'package',
            'itemid' => 0, 'filepath' => '/', 'filename' => 'fixture.h5p',
        ], $path);
        unlink($path);
        return [$course, $activity, $file, $directory];
    }

    /**
     * Drag text retains blanks, correct words, hints, feedback, local media and source bytes.
     */
    public function test_drag_text_and_passive_assets(): void {
        $this->resetAfterTest();
        [$course, $activity, $file, $directory] = $this->fixture('H5P.DragText', [
            'taskDescription' => '<p>Choose <img src="images/été.png" alt="Photo"></p>',
            'textField' => 'The *cat:Animal\\+Well done\\-Try again* sleeps.', 'distractors' => '*dog*',
        ], ['content/images/été.png' => 'image bytes', 'content/payload.js' => 'alert(1)',
            'content/unsafe.svg' => '<svg onload="alert(1)"></svg>']);
        $modinfo = get_fast_modinfo($course);
        $result = h5p_exporter::render($modinfo->get_cm($activity->cmid), $modinfo, $directory);
        $html = file_get_contents($directory . '/' . $result['filename']);
        foreach (['[____]', '<strong>cat</strong>', 'Animal', 'Well done', 'Try again', 'CC BY', 'index.html'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $base = '/files/' . $activity->cmid . '/h5p/0/';
        $this->assertSame($file->get_content(), file_get_contents($directory . $base . 'source.h5p'));
        $this->assertSame('image bytes', file_get_contents($directory . $base . 'media/images/été.png'));
        $this->assertStringContainsString('images/%C3%A9t%C3%A9.png', $html);
        $this->assertFileDoesNotExist($directory . $base . 'media/payload.js');
        $this->assertFileDoesNotExist($directory . $base . 'media/unsafe.svg');
        $this->assertStringNotContainsString('<input', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    /**
     * Static drop-zone maps preserve multiple valid targets and original positions.
     */
    public function test_drag_question_mapping(): void {
        $this->resetAfterTest();
        [$course, $activity, $file, $directory] = $this->fixture('H5P.DragQuestion', [
            'question' => ['settings' => ['background' => ['path' => 'map.png']], 'task' => [
                'elements' => [['type' => ['library' => 'H5P.AdvancedText 1.1', 'params' => ['text' => 'Paris']]]],
                'dropZones' => [
                    ['label' => 'France', 'x' => 20, 'y' => 30, 'width' => 10, 'height' => 15, 'correctElements' => ['0']],
                    ['label' => 'Europe', 'x' => 50, 'y' => 40, 'correctElements' => ['0']],
                ],
            ]],
        ], ['content/map.png' => 'map']);
        $renderer = new h5p_renderer(new h5p_package($file, $directory, $activity->cmid));
        $package = new h5p_package($file, $directory, $activity->cmid);
        $html = $renderer->render('H5P.DragQuestion 1.15', $package->content);
        $this->assertStringContainsString('left:20%;top:30%;width:10%;height:15%', $html);
        $this->assertStringContainsString('France', $html);
        $this->assertStringContainsString('Europe', $html);
        $this->assertSame(3, substr_count($html, 'Paris'));
    }

    /**
     * Composite questions become separate navigable pages and survive root reorganisation.
     */
    public function test_question_set_navigation_and_solutions(): void {
        $this->resetAfterTest();
        [$course, $activity, $file, $directory] = $this->fixture('H5P.QuestionSet', [
            'poolSize' => 1, 'randomQuestions' => true, 'passPercentage' => 75,
            'questions' => [
                ['library' => 'H5P.MultiChoice 1.16', 'metadata' => ['title' => 'Capital'], 'params' => [
                    'question' => 'Capital of France?', 'answers' => [
                        ['text' => 'Paris', 'correct' => true], ['text' => 'London', 'correct' => false],
                    ],
                ]],
                ['library' => 'H5P.TrueFalse 1.8', 'params' => ['question' => 'The earth is round', 'correct' => 'true']],
            ],
        ]);
        $modinfo = get_fast_modinfo($course);
        $result = h5p_exporter::render($modinfo->get_cm($activity->cmid), $modinfo, $directory);
        $this->assertCount(3, $result['filenames']);
        $part = 'h5pactivity-' . $activity->cmid . '-part-1.html';
        $html = file_get_contents($directory . '/' . $part);
        $this->assertStringContainsString('Paris', $html);
        $this->assertStringContainsString('London', $html);
        $this->assertStringContainsString('part-2.html', $html);
        $this->assertStringContainsString('Solutions', $html);
        $files = [];
        foreach ($result['filenames'] as $name) {
            $files[$name] = $directory . '/' . $name;
        }
        file_put_contents($directory . '/index.html', '<a href="' . $result['filename'] . '">H5P</a>');
        $files['index.html'] = $directory . '/index.html';
        $source = 'files/' . $activity->cmid . '/h5p/0/source.h5p';
        $files[$source] = $directory . '/' . $source;
        $organised = layout::organise($files);
        $this->assertStringContainsString('href="../index.html"', file_get_contents($organised['resources/' . $part]));
        $this->assertSame($file->get_content(), file_get_contents($organised['resources/' . $source]));
        $main = file_get_contents($organised['resources/' . $result['filename']]);
        $this->assertStringContainsString('poolSize', $main);
        $this->assertStringContainsString('75', $main);
    }

    /**
     * Slides retain nested text, comments and real previous/next/specified links.
     */
    public function test_presentation_slides(): void {
        $this->resetAfterTest();
        [$course, $activity, $file, $directory] = $this->fixture('H5P.CoursePresentation', [
            'presentation' => ['slides' => [
                ['elements' => [['action' => ['library' => 'H5P.AdvancedText 1.1', 'params' => ['text' => 'First slide']],
                    'solution' => 'Speaker comment', 'goToSlideType' => 'specified', 'goToSlide' => 2]]],
                ['elements' => [['action' => ['library' => 'H5P.Image 1.1', 'params' => [
                    'file' => ['path' => 'photo.png'], 'alt' => 'Landscape',
                ]]]]],
            ]],
        ], ['content/photo.png' => 'photo']);
        $modinfo = get_fast_modinfo($course);
        $result = h5p_exporter::render($modinfo->get_cm($activity->cmid), $modinfo, $directory);
        $this->assertCount(3, $result['filenames']);
        $html = file_get_contents($directory . '/' . $result['filenames'][0]);
        $this->assertStringContainsString('First slide', $html);
        $this->assertStringContainsString('Speaker comment', $html);
        $this->assertStringContainsString('part-2.html', $html);
        $html = file_get_contents($directory . '/' . $result['filenames'][1]);
        $this->assertStringContainsString('photo.png', $html);
        $this->assertStringContainsString('part-1.html', $html);
    }

    /**
     * Video retains local playback, time-ordered interactions and branching descriptions.
     */
    public function test_interactive_video_timeline(): void {
        $this->resetAfterTest();
        [$course, $activity, $file, $directory] = $this->fixture('H5P.InteractiveVideo', [
            'interactiveVideo' => ['video' => ['files' => [['path' => 'film.mp4'], ['path' => 'https://example.org/video']]],
                'assets' => ['interactions' => [
                    ['duration' => ['from' => 65, 'to' => 80], 'label' => 'Later',
                        'action' => ['library' => 'H5P.TrueFalse 1.8', 'params' => ['question' => 'Ready?', 'correct' => 'true']],
                        'adaptivity' => ['wrong' => ['seekTo' => 10, 'message' => 'Review this part']]],
                    ['duration' => ['from' => 10, 'to' => 20], 'label' => 'Earlier',
                        'action' => ['library' => 'H5P.Text 1.1', 'params' => ['text' => 'Introduction']]],
                ]]],
        ], ['content/film.mp4' => 'video bytes']);
        $package = new h5p_package($file, $directory, $activity->cmid);
        $renderer = new h5p_renderer($package);
        $html = $renderer->render('H5P.InteractiveVideo 1.28', $package->content);
        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('film.mp4', $html);
        $this->assertLessThan(strpos($html, 'Later'), strpos($html, 'Earlier'));
        $this->assertStringContainsString('00:01:05', $html);
        $this->assertStringContainsString('00:00:10', $html);
        $this->assertStringContainsString('Review this part', $html);
        $this->assertStringContainsString('https://example.org/video', $html);
        $this->assertStringContainsString('Media not embedded', $html);
    }

    /**
     * Unsupported libraries are visible, and authored scripts or network images never execute.
     */
    public function test_unsupported_and_unsafe_markup(): void {
        $this->resetAfterTest();
        [$course, $activity, $file, $directory] = $this->fixture('H5P.DragText', []);
        $renderer = new h5p_renderer(new h5p_package($file, $directory, $activity->cmid));
        $html = $renderer->rich('<script>alert(1)</script><form><input name="answer"></form>' .
            '<img src="https://example.org/tracker.png" onerror="alert(2)">' .
            '<a href="javascript:alert(3)">Unsafe</a><iframe src="https://example.org"></iframe>');
        foreach (['<script', '<input', '<form', '<iframe', 'onerror=', 'src="https:', 'href="javascript:'] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, $html);
        }
        $this->assertStringContainsString('not converted', $renderer->render('H5P.FutureType 1.0', []));
        $this->assertStringContainsString('not converted', $renderer->render('H5P.DragText 2.0', []));
    }

    /**
     * Traversal entries are rejected before any content extraction.
     */
    public function test_package_rejects_traversal(): void {
        $this->resetAfterTest();
        [$course, $activity, $file, $directory] = $this->fixture('H5P.DragText', [], ['content/../../escaped.png' => 'bad']);
        $this->expectException(\moodle_exception::class);
        new h5p_package($file, $directory, $activity->cmid);
    }

    /**
     * Reports preserve Moodle answers but disappear without review capability or tracking.
     */
    public function test_historical_attempt_permissions(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $file, $directory] = $this->fixture('H5P.DragText', ['textField' => '*word*']);
        $generator = $this->getDataGenerator();
        $student = $generator->create_user(['firstname' => 'Testlearner']);
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $attempt = $generator->get_plugin_generator('mod_h5pactivity')->create_content($activity, [
            'cmid' => $activity->cmid, 'userid' => $student->id,
        ]);
        $this->setUser($teacher);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($activity->cmid);
        $result = h5p_exporter::render($cm, $modinfo, $directory);
        $report = 'h5pactivity-' . $cm->id . '-attempt-' . $attempt->id . '.html';
        $this->assertContains($report, $result['filenames']);
        $html = file_get_contents($directory . '/' . $report);
        $this->assertStringContainsString('Testlearner', $html);
        $this->assertStringContainsString('Blueberry', $html);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        assign_capability('mod/h5pactivity:reviewattempts', CAP_PROHIBIT, $roleid, \context_module::instance($cm->id)->id);
        accesslib_clear_all_caches_for_unit_testing();
        $result = h5p_exporter::render($cm, $modinfo, make_temp_directory('flatarchive-no-report-' . random_string()));
        $this->assertCount(1, $result['filenames']);
        $this->setAdminUser();
        $DB->set_field('h5pactivity', 'enabletracking', 0, ['id' => $activity->id]);
        $result = h5p_exporter::render($cm, $modinfo, make_temp_directory('flatarchive-no-tracking-' . random_string()));
        $this->assertCount(1, $result['filenames']);
    }

    /**
     * Separate groups and content-authoring permission remain enforced for exports.
     */
    public function test_groups_and_authoring_permission(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $activity, $file, $directory] = $this->fixture('H5P.DragText', ['textField' => '*answer*']);
        $generator = $this->getDataGenerator();
        $teacher = $generator->create_user();
        $students = [$generator->create_user(), $generator->create_user()];
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $groups = [$generator->create_group(['courseid' => $course->id]), $generator->create_group(['courseid' => $course->id])];
        $generator->create_group_member(['groupid' => $groups[0]->id, 'userid' => $teacher->id]);
        foreach ($students as $i => $student) {
            $generator->enrol_user($student->id, $course->id, 'student');
            $generator->create_group_member(['groupid' => $groups[$i]->id, 'userid' => $student->id]);
            $generator->get_plugin_generator('mod_h5pactivity')->create_content($activity, [
                'cmid' => $activity->cmid, 'userid' => $student->id,
            ]);
        }
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $activity->cmid]);
        $context = \context_module::instance($activity->cmid);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $roleid, $context->id);
        rebuild_course_cache($course->id, true);
        $this->setUser($teacher);
        $modinfo = get_fast_modinfo($course);
        $result = h5p_exporter::render($modinfo->get_cm($activity->cmid), $modinfo, $directory);
        $this->assertCount(2, $result['filenames']);
        $this->setUser($students[0]);
        $modinfo = get_fast_modinfo($course);
        $this->expectException(\required_capability_exception::class);
        h5p_exporter::render($modinfo->get_cm($activity->cmid), $modinfo, $directory);
    }
}
