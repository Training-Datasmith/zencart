<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Traits;

use Zencart\Db_Repositories\Plugin_Control_Repository;
use Zencart\Db_Repositories\Plugin_Control_Version_Repository;
use Zencart\Page_Loader\Page_Loader;
use Zencart\Plugin_Manager\Plugin_Manager;
/**
 * @since ZC v2.1.0
 */
trait Interacts_With_Plugins
{
    protected bool $is_a_zc_plugin = false;
    protected string $zc_plugin_dir_name;
    protected string $zc_plugin_version_dir;
    protected string $zc_plugin_path;
    /** @var string catalog, admin, or Installer */
    protected string $zc_plugin_context;
    /** @var string working directory of currently installed version */
    protected string $plugin_manager_installed_version_directory;
    /** @var string will be null if no 'catalog' dir present (no catalog features) */
    protected string $zc_plugin_catalog_path;
    /** @var string will be null if no 'admin' dir present (no admin features) */
    protected string $zc_plugin_admin_path;
    /** @var string will be null if no 'Installer' dir present (should never be) */
    protected string $zc_plugin_installer_path;
    /**
     * Determine the plugin's currently-installed zc_plugin directory.
     * @since ZC v2.1.0
     */
    protected function detect_zc_plugin_details(string $__dir__path): void
    {
        $is_in_zc_plugins_directory = \str_contains($__dir__path, 'zc_plugins');
        if (!$is_in_zc_plugins_directory) {
            return;
        }
        $__dir__path = str_replace('\\', '/', $__dir__path);
        $match = str_replace(rtrim(DIR_FS_CATALOG, '\/') . '/zc_plugins/', '', $__dir__path);
        $matches = explode('/', $match);
        $this->zc_plugin_dir_name = $matches[0];
        $this->zc_plugin_version_dir = $matches[1];
        $this->zc_plugin_context = $matches[2];
        // 'admin' or 'catalog' or 'Installer'
        $this->zc_plugin_path = str_replace('//', '/', DIR_FS_CATALOG . '/zc_plugins/' . $this->zc_plugin_dir_name . '/' . $this->zc_plugin_version_dir . '/');
        $this->is_a_zc_plugin = \file_exists($this->zc_plugin_path . 'manifest.php');
        global $db;
        $plugin_manager = new Plugin_Manager(new Plugin_Control_Repository($db), new Plugin_Control_Version_Repository($db));
        $this->plugin_manager_installed_version_directory = $plugin_manager->get_plugin_version_directory($this->zc_plugin_dir_name, $plugin_manager->get_installed_plugins());
        $installed_plugin_path = rtrim(str_replace(DIR_FS_CATALOG, '', $this->plugin_manager_installed_version_directory), '/');
        if ($this->zc_plugin_context === 'catalog') {
            $this->zc_plugin_catalog_path = $installed_plugin_path . '/catalog/';
        }
        if ($this->zc_plugin_context === 'admin') {
            $this->zc_plugin_admin_path = $installed_plugin_path . '/admin/';
        }
        if ($this->zc_plugin_context === 'Installer') {
            $this->zc_plugin_installer_path = $installed_plugin_path . '/Installer/';
        }
    }
    /**
     * @var \template_func $template
     * @var PageLoader $pageLoader
     * @since ZC v2.1.0
     */
    protected function link_catalog_stylesheet(string $stylesheet_filename, ?string $current_page): bool
    {
        global $template, $page_loader;
        if (!$page_loader) {
            $page_loader = Page_Loader::get_instance();
        }
        $found = false;
        // link zc_plugin stylesheet
        $stylesheet_filename = basename($stylesheet_filename);
        if (file_exists($file = $page_loader->get_template_plugin_dir($stylesheet_filename, 'css', $this->zc_plugin_dir_name) . $stylesheet_filename)) {
            echo '<link rel="stylesheet" href="' . $file . '">' . "\n";
            $found = true;
        }
        // if catalog template contains a stylesheet of the same name, load it as well, to apply any overrides it may contain
        $stylesheet_dir = $template->get_template_dir($stylesheet_filename, DIR_WS_TEMPLATE, $current_page, 'css') . '/';
        if (!str_contains($stylesheet_dir, $this->zc_plugin_catalog_path) && file_exists($stylesheet_dir . $stylesheet_filename)) {
            echo '<link rel="stylesheet" href="' . $stylesheet_dir . $stylesheet_filename . '">' . "\n";
            $found = true;
        }
        return $found;
    }
}