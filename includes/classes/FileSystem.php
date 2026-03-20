<?php

declare (strict_types=1);
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\File_System;

/**
 * @since ZC v1.5.7
 */
class File_System
{
    /**
     * @since ZC v1.5.7
     */
    public function load_files_from_directory(string $root_dir, string $file_regx = '~^[^\._].*\.php$~i'): void
    {
        if (!is_dir($root_dir)) {
            return;
        }
        if (!$dir = @dir($root_dir)) {
            return;
        }
        while ($file = $dir->read()) {
            if (preg_match($file_regx, $file) > 0) {
                require_once $root_dir . '/' . $file;
            }
        }
        $dir->close();
    }
    /**
     * @since ZC v1.5.7
     */
    public function list_files_from_directory(string $root_dir, string $file_regx = '~^[^\._].*\.php$~i', bool $keep_dir = false): array
    {
        if (!is_dir($root_dir)) {
            return [];
        }
        if (!$dir = @dir($root_dir)) {
            return [];
        }
        $file_list = [];
        while ($file = $dir->read()) {
            if (preg_match($file_regx, $file) > 0) {
                $file_name = $root_dir . '/' . $file;
                if ($keep_dir === false) {
                    $file_name = basename($file_name);
                }
                $file_list[] = $file_name;
            }
        }
        $dir->close();
        return $file_list;
    }
    /**
     * @since ZC v1.5.8
     */
    public function list_files_from_directory_alpha_sorted(string $root_dir, string $file_regx = '~^[^\._].*\.php$~i', bool $keep_dir = false): array
    {
        $file_list = $this->list_files_from_directory($root_dir, $file_regx, $keep_dir);
        sort($file_list);
        return $file_list;
    }
    /**
     * @since ZC v1.5.7
     */
    public function load_files_from_plugins_directory(array $installed_plugins, string $root_dir, string $file_regx = '~^[^\._].*\.php$~i'): void
    {
        foreach ($installed_plugins as $plugin) {
            $plugin_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'];
            $plugin_dir = $plugin_dir . '/' . $root_dir;
            $this->load_files_from_directory($plugin_dir, $file_regx);
        }
    }
    /**
     * @since ZC v1.5.7
     */
    public function find_plugin_admin_page(array $installed_plugins, string $page): ?string
    {
        $found = null;
        foreach ($installed_plugins as $plugin) {
            $plugin_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'];
            $admin_file = $plugin_dir . '/admin/' . $page . '.php';
            $admin_file = $this->realpath($admin_file);
            $real_path = $this->realpath($admin_file);
            if (!str_starts_with($real_path, $plugin_dir)) {
                continue;
                // Skip this file if it's not under the intended directory
            }
            if (!file_exists($real_path)) {
                continue;
            }
            $found = $real_path;
        }
        return $found;
    }
    /**
     * @since ZC v1.5.7
     */
    public function is_admin_dir(string $file_path): bool
    {
        if (!defined('DIR_FS_ADMIN')) {
            return false;
        }
        $test = str_replace(DIR_FS_ADMIN, '', $file_path);
        if ($test != $file_path) {
            return false;
        }
        return true;
    }
    /**
     * @since ZC v1.5.7
     */
    public function is_catalog_dir(string $file_path): bool
    {
        if ($this->is_admin_dir($file_path)) {
            return false;
        }
        if (!defined('DIR_FS_CATALOG')) {
            return false;
        }
        $test = str_replace(DIR_FS_CATALOG, '', $file_path);
        if ($test !== $file_path) {
            return false;
        }
        return true;
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_relative_dir(string $file_path): string
    {
        if ($this->is_admin_dir($file_path)) {
            return str_replace(DIR_FS_ADMIN, '', $file_path);
        }
        if ($this->is_catalog_dir($file_path)) {
            return str_replace(DIR_FS_CATALOG, '', $file_path);
        }
        return $file_path;
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_directory_size(string $path, $decimals = 2, bool $add_suffix = true): string
    {
        $bytes = 0;
        foreach (new \Recursive_Iterator_Iterator(new \Recursive_Directory_Iterator($path)) as $file) {
            $bytes += $file->get_size();
        }
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        $suffix = 'bloody huge!';
        if (isset($size[$factor])) {
            $suffix = $size[$factor];
        }
        return sprintf("%.{$decimals}f ", $bytes / 1024 ** $factor) . $suffix;
    }
    /**
     * @since ZC v1.5.7
     */
    public function file_exists_in_directory(string $file_dir, string $file_pattern): bool
    {
        $found = false;
        $file_pattern = '/' . str_replace('/', "\\/", $file_pattern) . '$/';
        if (!is_dir($file_dir)) {
            return false;
        }
        if ($mydir = @dir($file_dir)) {
            while ($file = $mydir->read()) {
                if (preg_match($file_pattern, $file)) {
                    $found = true;
                    break;
                }
            }
            $mydir->close();
        }
        return $found;
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_file_extension(string $file, string $extension = 'php'): string
    {
        if (preg_match('~\.' . $extension . '~i', $file)) {
            return $file;
        }
        return $file . '.php';
    }
    /**
     * @since ZC v1.5.8
     */
    public function has_template_language_override(string $template_dir, string $root_path, string $language, string $file, string $extra_path = ''): bool
    {
        $file = $this->set_file_extension($file);
        $full_path = $root_path . $language . $extra_path . '/' . $template_dir . '/' . $file;
        if (!file_exists($full_path)) {
            return false;
        }
        return true;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_extra_path_for_template_overrride_or_original(string $template_dir, string $root_path, string $language, string $file, string $extra_path = ''): string
    {
        if (!$this->has_template_language_override($template_dir, $root_path, $language, $file, $extra_path)) {
            return $extra_path;
        }
        return $extra_path . '/' . $template_dir;
    }
    /**
     * @since ZC v2.0.0
     */
    protected function realpath(string $path): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return str_replace('\\', '/', realpath($path));
        }
        return realpath($path);
    }
    /**
     * @since ZC v2.2.0
     */
    public function delete_directory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }
        $items = scandir($directory);
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.') {
                continue;
            }
            if ($item === '..') {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->delete_directory($path);
                continue;
            }
            @unlink($path);
        }
        return @rmdir($directory);
    }
}