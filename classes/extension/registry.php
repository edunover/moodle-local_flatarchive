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

namespace local_flatarchive\extension;

use local_flatarchive\archive\paths;
use local_flatarchive\archive\selection;

/**
 * Discover optional exporters and enforce their declared Moodle branch policy.
 *
 * Extensions execute trusted PHP; this contract is not a sandbox or a licence server.
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class registry {
    /** @var int Extension API version. */
    public const API_VERSION = 1;
    /** @var array Exporters indexed by module; null marks conflicting providers. */
    private array $exporters = [];

    /**
     * Build a deterministic registry; collisions fail closed.
     * @param exporter[] $exporters Discovered implementations
     */
    public function __construct(array $exporters) {
        foreach ($exporters as $exporter) {
            if (!$exporter instanceof exporter) {
                throw new \coding_exception('Invalid Flat Archive exporter');
            }
            $module = $exporter->module();
            if (!preg_match('/\A[a-z][a-z0-9_]*\z/', $module) || selection::native($module)) {
                continue;
            }
            $this->exporters[$module] = array_key_exists($module, $this->exporters) ? null : $exporter;
        }
    }

    /**
     * Discover only installed local plugins implementing the explicit contract.
     * @return self
     */
    public static function installed(): self {
        $exporters = [];
        foreach (\core_component::get_plugin_list('local') as $name => $directory) {
            $component = 'local_' . $name;
            if ($name === 'flatarchive' || !get_config($component, 'version')) {
                continue;
            }
            $class = $component . '\\flatarchive_provider';
            if (is_subclass_of($class, provider::class) && $class::api_version() === self::API_VERSION) {
                $exporters = array_merge($exporters, $class::exporters());
            }
        }
        return new self($exporters);
    }

    /**
     * Whether exactly one extension supports the current branch.
     * @param string $module Activity type
     * @param int|null $branch Optional explicit branch for compatibility checks
     * @return bool
     */
    public function available(string $module, ?int $branch = null): bool {
        global $CFG;
        $branch = $branch ?? (int)$CFG->branch;
        return isset($this->exporters[$module]) && in_array($branch, $this->exporters[$module]->branches(), true);
    }

    /**
     * Localised reason why an extension is unavailable; empty for unknown modules.
     * @param string $module Activity type
     * @return string
     */
    public function reason(string $module): string {
        if (!array_key_exists($module, $this->exporters)) {
            return '';
        }
        if ($this->exporters[$module] === null) {
            return get_string('extensionconflict', 'local_flatarchive');
        }
        return $this->available($module) ? '' : get_string('extensionbranch', 'local_flatarchive');
    }

    /**
     * Check branch, visibility, course archive permission and extension policy.
     * @param \cm_info $cm Activity
     * @return bool
     */
    public function permitted(\cm_info $cm): bool {
        return $this->available($cm->modname) && $cm->uservisible
            && has_capability('local/flatarchive:archive', \context_course::instance($cm->course))
            && $this->exporters[$cm->modname]->permitted($cm);
    }

    /**
     * Dispatch an authorised extension into its own directory and validate its result.
     * @param \cm_info $cm Activity
     * @param \course_modinfo $modinfo Course
     * @param string $directory Archive root
     * @param array $options Course options
     * @return array Native archive coordinator result
     */
    public function render(\cm_info $cm, \course_modinfo $modinfo, string $directory, array $options): array {
        if ((int)$modinfo->get_course()->id !== (int)$cm->course || !$this->permitted($cm)) {
            throw new \moodle_exception('invalidselection', 'local_flatarchive');
        }
        $prefix = 'extensions/' . $cm->id;
        $target = $directory . '/' . $prefix;
        if (file_exists($target) || !mkdir($target, 0755, true)) {
            throw new \moodle_exception('archivefailed', 'local_flatarchive');
        }
        $result = $this->exporters[$cm->modname]->render($cm, $modinfo, $target, $options);
        $files = self::validate_result($result, $target);
        $filename = $prefix . '/' . $result['filename'];
        return [
            'filename' => $filename,
            'filenames' => array_map(fn($file) => $prefix . '/' . $file, $files),
            'item' => \html_writer::tag('li', \html_writer::link($filename, s($cm->name))),
        ];
    }

    /**
     * Reject missing documents, traversal and symlinks outside the assigned directory.
     * @param array $result Exporter result
     * @param string $directory Assigned directory
     * @return string[] Validated filenames
     */
    public static function validate_result(array $result, string $directory): array {
        $main = $result['filename'] ?? null;
        $files = $result['filenames'] ?? null;
        if (!is_string($main) || !str_ends_with($main, '.html') || !is_array($files) || !in_array($main, $files, true)) {
            throw new \coding_exception('Invalid Flat Archive extension result');
        }
        $root = realpath($directory);
        foreach ($files as $file) {
            if (!is_string($file) || !paths::relative($file)) {
                throw new \coding_exception('Invalid Flat Archive extension path');
            }
            $real = realpath($directory . '/' . $file);
            if (!$root || !$real || !str_starts_with($real, $root . DIRECTORY_SEPARATOR) || !is_file($real)) {
                throw new \coding_exception('Flat Archive extension file is outside its directory or missing');
            }
        }
        return array_values(array_unique($files));
    }
}
