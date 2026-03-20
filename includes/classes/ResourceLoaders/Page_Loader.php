<?php

declare (strict_types=1);
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Page_Loader;

use Zencart\File_System\File_System as FileSystem;
use Zencart\Traits\Singleton;
/**
 * @since ZC v1.5.7
 */
class Page_Loader
{
    use Singleton;
    private array $installed_plugins;
    private string $main_page;
    private File_System $file_system;
    /**
     * @since ZC v1.5.8
     */
    public function init(array $installed_plugins, string $main_page, File_System $file_system): void
    {
        $this->installed_plugins = $installed_plugins;
        $this->main_page = $main_page;
        $this->file_system = $file_system;
    }
    // -----
    // This method locates the 'base' module-page directory, either in the
    // storefront's /includes/modules/pages or in an encapsulated plugin's
    // /catalog/includes/modules/pages directory.
    //
    /**
     * @since ZC v1.5.7
     */
    public function find_module_page_directory(string $context = 'catalog'): bool|string
    {
        if (is_dir(DIR_WS_MODULES . 'pages/' . $this->main_page)) {
            return DIR_WS_MODULES . 'pages/' . $this->main_page;
        }
        foreach ($this->installed_plugins as $plugin) {
            $root_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/' . $context;
            $check_dir = $root_dir . '/includes/modules/pages/' . $this->main_page;
            if (is_dir($check_dir)) {
                return $check_dir;
            }
        }
        return false;
    }
    // -----
    // This method locates **all** files matching a given pattern from the 'base'
    // module-page directory and any module-page directories found in zc_plugins.
    //
    /**
     * @since ZC v2.2.0
     */
    public function list_module_pages_files(string $name_starts_with, string $file_extension = '.php', string $context = 'catalog'): array
    {
        $module_page_dir = DIR_WS_MODULES . 'pages/' . $this->main_page;
        $file_regx = '~^' . $name_starts_with . '.*\\' . $file_extension . '$~i';
        $file_list = $this->file_system->list_files_from_directory_alpha_sorted($module_page_dir, $file_regx, true);
        foreach ($this->installed_plugins as $plugin) {
            $root_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/' . $context;
            $check_dir = $root_dir . '/' . $module_page_dir;
            $file_list = array_merge($file_list, $this->file_system->list_files_from_directory_alpha_sorted($check_dir, $file_regx, true));
        }
        return $file_list;
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_template_part(string $page_directory, string $template_part, string $file_extension = '.php'): array
    {
        $directory_array = $this->get_template_part_from_directory([], $page_directory, $template_part, $file_extension);
        foreach ($this->installed_plugins as $plugin) {
            $check_dir = 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/';
            $check_dir .= $page_directory;
            $directory_array = $this->get_template_part_from_directory($directory_array, $check_dir, $template_part, $file_extension);
        }
        sort($directory_array);
        return $directory_array;
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_template_part_from_directory(array $directory_array, string $page_directory, string $template_part, string $file_extension): array
    {
        if ($dir = @dir($page_directory)) {
            while ($file = $dir->read()) {
                if (!is_dir($page_directory . $file)) {
                    if (substr($file, strrpos($file, '.')) === $file_extension && preg_match($template_part, $file)) {
                        $directory_array[] = $file;
                    }
                }
            }
            $dir->close();
        }
        return $directory_array;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_template_directory(string $template_code, string $current_template, string $current_page, string $template_dir): string
    {
        if ($current_template === 'template_default') {
            $current_template = DIR_WS_TEMPLATES . $current_template . '/';
        }
        $path = DIR_WS_TEMPLATES . 'template_default/' . $template_dir;
        if ($this->file_system->file_exists_in_directory($current_template . $current_page, $template_code)) {
            return $current_template . $current_page . '/';
        }
        if ($this->file_system->file_exists_in_directory(DIR_WS_TEMPLATES . 'template_default/' . $current_page, preg_replace('/\//', '', $template_code))) {
            return DIR_WS_TEMPLATES . 'template_default/' . $current_page;
        }
        if ($this->file_system->file_exists_in_directory($current_template . $template_dir, preg_replace('/\//', '', $template_code))) {
            return $current_template . $template_dir;
        }
        if ($tpl_plugin_dir = $this->get_template_plugin_dir($template_code, $template_dir)) {
            return $tpl_plugin_dir;
        }
        return $path;
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_template_plugin_dir(string $template_code, string $template_dir, ?string $which_plugin = ''): bool|string
    {
        foreach ($this->installed_plugins as $plugin) {
            if (!empty($which_plugin) && $plugin['unique_key'] !== $which_plugin) {
                continue;
            }
            $check_dir = 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/includes/templates/default/' . $template_dir . '/';
            if ($this->file_system->file_exists_in_directory($check_dir, preg_replace('/\//', '', $template_code))) {
                return $check_dir;
            }
        }
        return false;
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_body_code(): string
    {
        if (file_exists(DIR_WS_MODULES . 'pages/' . $this->main_page . '/main_template_vars.php')) {
            return DIR_WS_MODULES . 'pages/' . $this->main_page . '/main_template_vars.php';
        }
        return $this->get_template_directory('tpl_' . preg_replace('/.php/', '', $this->main_page) . '_default.php', DIR_WS_TEMPLATE, $this->main_page, 'templates') . '/tpl_' . $this->main_page . '_default.php';
    }
}