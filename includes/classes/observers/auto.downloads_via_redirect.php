<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
/**
 * This observer class is intended to allow downloadable files to be served
 * by redirecting the customer's browser page to a temporary symlink on
 * the server; the symlinked file expires to help prevent theft
 * @since ZC v1.5.6
 */
class Zc_Observer_Downloads_Via_Redirect extends base
{
    /**
     * Folder where the symlink redirect folders will be generated. The folder requires "writable by PHP" permissions.
     * This is the path from the root of the filesystem.
     * @var string
     */
    private $pub_folder = DIR_FS_DOWNLOAD_PUBLIC;
    /**
     * Folder off the webroot where the "pub" symlinks will be accessible. Usually this is "pub".
     */
    private readonly string $ws_pub_folder;
    /**
     * Number of seconds before garbage-collection purges
     * the leftover symlink folders.
     * Default = 3600 = 1 hour
     */
    protected int $gc_cleanup_time;
    /**
     * Class constructor
     */
    public function __construct()
    {
        if (DOWNLOAD_BY_REDIRECT != 'true') {
            return;
        }
        $this->pub_folder = DIR_FS_DOWNLOAD_PUBLIC;
        $this->ws_pub_folder = HTTP_SERVER . DIR_WS_DOWNLOAD_PUBLIC;
        // attach listener
        $this->attach($this, ['NOTIFY_DOWNLOAD_READY_TO_REDIRECT']);
        $this->gc_cleanup_time = 0;
        if (defined('SYMLINK_GARBAGE_COLLECTION_THRESHOLD') && (int) SYMLINK_GARBAGE_COLLECTION_THRESHOLD > 300) {
            $this->gc_cleanup_time = (int) SYMLINK_GARBAGE_COLLECTION_THRESHOLD;
        }
    }
    /**
     * This fires when the download module is ready to process redirects
     *
     * @param string $eventID name of the observer event fired
     * @param array $array deprecated BC data
     * @param string $origin_filename (mutable)
     * @param string $browser_filename (mutable)
     * @param string $source_directory (mutable)
     * @param boolean $link_create_status (mutable)
     * @since ZC v1.5.6
     */
    protected function update_notify_download_ready_to_redirect(&$class, $event_id, $array, &$service, string &$origin_filename, &$browser_filename, string &$source_directory, &$link_create_status)
    {
        if (!defined('DOWNLOAD_CHMOD')) {
            define('DOWNLOAD_CHMOD', '0777');
        }
        $this->garbage_collection_unlink_temp_folders($this->pub_folder);
        $tempdir = $this->generate_random_name() . '-' . time();
        umask(00);
        mkdir($this->pub_folder . $tempdir, octdec((string) DOWNLOAD_CHMOD));
        $download_link = str_replace(['/', '\\'], '_', $browser_filename);
        $link_create_status = @symlink($source_directory . $origin_filename, $this->pub_folder . $tempdir . '/' . $download_link);
        if ($link_create_status == true) {
            $this->notify('NOTIFY_DOWNLOAD_VIA_SYMLINK___BEGINS', [$download_link, $origin_filename, $tempdir]);
            header('HTTP/1.1 303 See Other');
            zen_redirect($this->ws_pub_folder . $tempdir . '/' . $download_link, 303);
            zen_exit();
        }
    }
    /**
     * Returns a random name, 16 to 20 characters long
     * There are more than 10^28 combinations
     * This is used to build a random directory foldername. And, the directory is "hidden", ie: starts with '.'
     * @since ZC v1.5.6
     */
    private function generate_random_name(): string
    {
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $dirname = '.';
        if (defined('DOWNLOADS_SKIP_DOT_PREFIX_ON_REDIRECT') && DOWNLOADS_SKIP_DOT_PREFIX_ON_REDIRECT === true) {
            $dirname = '';
        }
        $length = floor(zen_rand(16, 20));
        for ($i = 1; $i <= $length; $i++) {
            $q = (int) floor(zen_rand(0, 25));
            $dirname .= $letters[$q];
        }
        return $dirname;
    }
    /**
     * Garbage collection for temporary download files/folders
     *
     * Unlinks (deletes) all subdirectories and files in $dir
     * Works only on one subdir level, will not recurse
     *
     * @param string $dir folder whose contents will be inspected for cleanup
     * @since ZC v1.5.6
     */
    private function garbage_collection_unlink_temp_folders(string $dir): void
    {
        $h1 = opendir($dir);
        while ($subdir = readdir($h1)) {
            // Ignore non directories
            if (!is_dir($dir . $subdir)) {
                continue;
            }
            if ($subdir == '.') {
                continue;
            }
            if ($subdir == '..') {
                continue;
            }
            // Loop and unlink files in subdirectory
            if ($h2 = opendir($dir . $subdir)) {
                [$fn, $exptime] = explode('-', $subdir);
                if ($exptime + $this->gc_cleanup_time > time()) {
                    continue;
                }
                while ($file = readdir($h2)) {
                    if ($file == '.') {
                        continue;
                    }
                    if ($file == '..') {
                        continue;
                    }
                    @unlink($dir . $subdir . '/' . $file);
                }
                closedir($h2);
            }
            @rmdir($dir . $subdir);
        }
        closedir($h1);
    }
}