<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Resource_Loaders;

use Zencart\File_System\File_System;
/**
 * @since ZC v2.1.0
 */
class Module_Finder
{
    private readonly string $module_dir;
    public function __construct(string $module_type, private readonly File_System $filesystem)
    {
        $this->module_dir = "{$module_type}/";
    }
    // -----
    // Locate all modules of the type specified during the class construction,
    // noting that any duplication in zc_plugins **overrides** any base module!
    //
    /**
     * @since ZC v2.1.0
     */
    public function find_from_filesystem(array $installed_plugins): array
    {
        $modules = [];
        $base_dir = DIR_WS_MODULES . $this->module_dir;
        $files = $this->filesystem->list_files_from_directory_alpha_sorted(DIR_FS_CATALOG . $base_dir);
        foreach ($files as $file) {
            $modules[$file] = $base_dir;
        }
        foreach ($installed_plugins as $plugin) {
            $plugin_dir = 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/includes/modules/' . $this->module_dir;
            $files = $this->filesystem->list_files_from_directory_alpha_sorted(DIR_FS_CATALOG . $plugin_dir);
            foreach ($files as $file) {
                $modules[$file] = $plugin_dir;
            }
        }
        return $modules;
    }
}