<?php
/**
 * Cache-busting for local CSS/JS files.
 *
 * Every local stylesheet/script is linked as file.css?v=<version>, where the
 * version combines CSMS_ASSET_VERSION with the file's last-modified time.
 * Editing a file changes its version automatically; bump CSMS_ASSET_VERSION
 * to force every browser to re-download all assets at once.
 */

if (!defined('CSMS_ASSET_VERSION')) {
    define('CSMS_ASSET_VERSION', '1.0.0');
}

if (!function_exists('csms_asset')) {
    /**
     * @param string $url     Asset URL as written in the page (relative or root-relative).
     * @param string $baseDir Directory the URL is relative to (usually __DIR__ of the page).
     */
    function csms_asset($url, $baseDir)
    {
        $file = rtrim($baseDir, '/\\') . '/' . ltrim($url, '/');
        $mtime = is_file($file) ? filemtime($file) : 0;
        $version = CSMS_ASSET_VERSION . ($mtime ? '.' . $mtime : '');

        return htmlspecialchars($url . '?v=' . $version, ENT_QUOTES, 'UTF-8');
    }
}
