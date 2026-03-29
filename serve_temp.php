<?php
// This file is part of Moodle - http://moodle.org/
//
// Sends temporary ZIP file then deletes the temp directory (local_flatarchive).
//
// @package    local_flatarchive
// @copyright  2025 Edunover
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_login();

// PARAM_RAW: path is validated below with realpath() to prevent directory traversal.
$relativepath = required_param('path', PARAM_RAW);
$relativepath = ltrim($relativepath, '/');

$fullpath = $CFG->tempdir . '/' . $relativepath;

if (!file_exists($fullpath) || !is_readable($fullpath)) {
    throw new moodle_exception('filenotfound', 'error', '', $relativepath);
}

// Vérifier que le chemin reste dans tempdir (sécurité).
$realtemp = realpath($CFG->tempdir);
$realfile = realpath($fullpath);
if ($realtemp === false || $realfile === false || strpos($realfile, $realtemp) !== 0) {
    throw new moodle_exception('filenotfound', 'error', '', $relativepath);
}

// Envoyer le fichier (headers + readfile pour pouvoir supprimer après).
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($fullpath) . '"');
header('Content-Length: ' . filesize($fullpath));
readfile($fullpath);

// Après envoi, supprimer le répertoire temporaire de cette archive.
$tempdir = dirname($fullpath);
if (is_dir($tempdir) && strpos(realpath($tempdir), $realtemp) === 0) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempdir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $item) {
        if ($item->isDir()) {
            @rmdir($item->getRealPath());
        } else {
            @unlink($item->getRealPath());
        }
    }
    @rmdir($tempdir);
}
exit;
