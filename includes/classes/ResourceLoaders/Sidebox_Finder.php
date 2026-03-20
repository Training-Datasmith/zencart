<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Resource_Loaders;

/**
 * @since ZC v1.5.8
 */
class Sidebox_Finder
{
    public function __construct(private $filesystem)
    {
    }
    /**
     * @since ZC v1.5.8
     */
    public function find_from_filesystem(array $installed_plugins, string $template_dir): array
    {
        $sideboxes = [];
        foreach ($installed_plugins as $plugin) {
            $plugin_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/includes/modules/sideboxes/';
            $files = $this->filesystem->list_files_from_directory_alpha_sorted($plugin_dir);
            foreach ($files as $file) {
                $sideboxes[$file] = $plugin['unique_key'] . '/' . $plugin['version'];
            }
        }
        $main_dir = DIR_FS_CATALOG_MODULES . 'sideboxes/';
        $main_dir_tpl = DIR_FS_CATALOG_MODULES . 'sideboxes/' . $template_dir . '/';
        $files = $this->filesystem->list_files_from_directory_alpha_sorted($main_dir);
        foreach ($files as $file) {
            $sideboxes[$file] = '';
        }
        $files = $this->filesystem->list_files_from_directory_alpha_sorted($main_dir_tpl);
        foreach ($files as $file) {
            $sideboxes[$file] = '';
        }
        return $sideboxes;
    }
    /**
     * @since ZC v1.5.8
     */
    public function sidebox_path(array $sidebox_info, string $template_dir, bool $with_full_path = false): bool|string
    {
        if (!empty($sidebox_info['plugin_details'])) {
            $path = $this->sidebox_path_in_plugin($sidebox_info);
            return $with_full_path ? DIR_FS_CATALOG . 'zc_plugins/' . $path . '/catalog/includes/modules/sideboxes/' : $path . '/';
        }
        $base_dir = DIR_FS_CATALOG . DIR_WS_MODULES . 'sideboxes/';
        $root_path = $with_full_path ? DIR_FS_CATALOG . DIR_WS_MODULES : '';
        if (file_exists($base_dir . $template_dir . '/' . $sidebox_info['layout_box_name'])) {
            return $root_path . 'sideboxes/' . $template_dir . '/';
        }
        if (file_exists($base_dir . $sidebox_info['layout_box_name'])) {
            return $root_path . 'sideboxes/';
        }
        return false;
    }
    /**
     * @since ZC v1.5.8
     */
    public function sidebox_path_in_plugin(array $sidebox_info): bool|string
    {
        $base_dir = DIR_FS_CATALOG . 'zc_plugins/' . $sidebox_info['plugin_details'] . '/' . 'catalog/includes/modules/sideboxes/';
        if (file_exists($base_dir . $sidebox_info['layout_box_name'])) {
            return $sidebox_info['plugin_details'];
        }
        return false;
    }
}