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
 * Read H5P data and copy passive assets without deploying its executable libraries.
 *
 * @package local_flatarchive
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class h5p_package {
    /** @var array Package metadata. */
    public array $metadata;
    /** @var array Content parameters. */
    public array $content;
    /** @var string Relative source package path. */
    public string $source;
    /** @var array Original content paths mapped to archive paths. */
    private array $assets = [];

    /**
     * Validate a package and copy its media to an isolated directory.
     *
     * @param \stored_file $file Original Moodle package.
     * @param string $directory Archive directory.
     * @param int $cmid Course module ID.
     */
    public function __construct(\stored_file $file, string $directory, int $cmid) {
        $base = paths::assets($cmid, 'h5p', 0);
        if (!make_writable_directory($directory . '/' . $base)) {
            throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
        }
        $this->source = $base . '/source.h5p';
        if (!$file->copy_content_to($directory . '/' . $this->source)) {
            throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
        }
        $zip = new \ZipArchive();
        if ($zip->open($directory . '/' . $this->source) !== true) {
            throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
        }
        try {
            $total = 0;
            $seen = [];
            if ($zip->numFiles > 20000) {
                throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (!$stat || $stat['size'] < 0) {
                    throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
                }
                $name = $stat['name'];
                $key = strtolower($name);
                $total += $stat['size'];
                $zip->getExternalAttributesIndex($i, $system, $attributes);
                if (
                    !self::safe_path(rtrim($name, '/')) || isset($seen[$key]) ||
                    (($attributes >> 16) & 0170000) === 0120000 || $total > 2147483648
                ) {
                    throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
                }
                $seen[$key] = true;
            }
            $this->metadata = self::read_json($zip, 'h5p.json');
            $this->content = self::read_json($zip, 'content/content.json');
            // Never unpack HTML, SVG, scripts or libraries into the browsable archive.
            $extensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'mp4', 'webm', 'ogv', 'mp3', 'ogg', 'wav', 'm4a', 'vtt'];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (
                    strpos($name, 'content/') !== 0 ||
                    !in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $extensions, true)
                ) {
                    continue;
                }
                $relative = substr($name, 8);
                $target = $base . '/media/' . $relative;
                make_writable_directory(dirname($directory . '/' . $target));
                $input = $zip->getStream($name);
                $output = fopen($directory . '/' . $target, 'wb');
                if (!$input || !$output) {
                    throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
                }
                try {
                    $expected = $zip->statName($name)['size'];
                    $copied = stream_copy_to_stream($input, $output, $expected + 1);
                    if ($copied !== $zip->statName($name)['size']) {
                        throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
                    }
                } finally {
                    fclose($input);
                    fclose($output);
                }
                $this->assets[$relative] = $target;
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Reject paths which could escape their package directory.
     *
     * @param string $path Candidate path.
     * @return bool Whether the path is a plain relative path.
     */
    public static function safe_path(string $path): bool {
        return paths::relative($path);
    }

    /**
     * Resolve a copied asset, never an arbitrary filesystem path.
     *
     * @param string $path H5P content path.
     * @return string|null Encoded relative archive URL.
     */
    public function asset(string $path): ?string {
        $path = rawurldecode($path);
        if (!isset($this->assets[$path])) {
            return null;
        }
        return implode('/', array_map('rawurlencode', explode('/', $this->assets[$path])));
    }

    /**
     * Read bounded JSON data without interpreting executable code.
     *
     * @param \ZipArchive $zip Package.
     * @param string $name Entry name.
     * @return array Decoded object.
     */
    private static function read_json(\ZipArchive $zip, string $name): array {
        $stat = $zip->statName($name);
        if (!$stat || $stat['size'] > 16777216) {
            throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
        }
        $json = $zip->getFromName($name, 16777217);
        if ($json === false || strlen($json) !== $stat['size']) {
            throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
        }
        $data = json_decode($json, true, 128);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('h5pinvalid', 'local_flatarchive');
        }
        return $data;
    }
}
