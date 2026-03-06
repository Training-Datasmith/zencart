<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */

namespace Zencart\ResourceLoaders;

/**
 * @since ZC v1.5.8
 */
class SideboxFinder
{
    public function __construct(private $filesystem)
    {
    }

    /**
     * @since ZC v1.5.8
     */
    public function findFromFilesystem(array $installedPlugins, string $templateDir): array
    {
        $sideboxes = [];
        foreach ($installedPlugins as $plugin) {
            $pluginDir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/includes/modules/sideboxes/';
            $files = $this->filesystem->listFilesFromDirectoryAlphaSorted($pluginDir);
            foreach ($files as $file) {
                $sideboxes[$file] = $plugin['unique_key'] . '/' . $plugin['version'];
            }
        }
        $mainDir = DIR_FS_CATALOG_MODULES . 'sideboxes/';
        $mainDirTpl = DIR_FS_CATALOG_MODULES . 'sideboxes/' . $templateDir . '/';
        $files = $this->filesystem->listFilesFromDirectoryAlphaSorted($mainDir);
        foreach ($files as $file) {
            $sideboxes[$file] = '';
        }
        $files = $this->filesystem->listFilesFromDirectoryAlphaSorted($mainDirTpl);
        foreach ($files as $file) {
            $sideboxes[$file] = '';
        }
        return $sideboxes;
    }

    /**
     * @since ZC v1.5.8
     */
    public function sideboxPath(array $sideboxInfo, string $templateDir, bool $withFullPath = false): bool|string
    {
        if (!empty($sideboxInfo['plugin_details'])) {
            $path = $this->sideboxPathInPlugin($sideboxInfo);
            return ($withFullPath) ? DIR_FS_CATALOG . 'zc_plugins/' . $path . '/catalog/includes/modules/sideboxes/' : ($path . '/');
        }
        $baseDir = DIR_FS_CATALOG . DIR_WS_MODULES . 'sideboxes/';
        $rootPath = ($withFullPath) ? DIR_FS_CATALOG . DIR_WS_MODULES : '';
        if (file_exists($baseDir . $templateDir . '/' . $sideboxInfo['layout_box_name'])) {
            return $rootPath . 'sideboxes/' . $templateDir . '/';
        }
        if (file_exists($baseDir . $sideboxInfo['layout_box_name'])) {
            return $rootPath . 'sideboxes/';
        }
        return false;
    }

    /**
     * @since ZC v1.5.8
     */
    public function sideboxPathInPlugin(array $sideboxInfo): bool|string
    {
        $baseDir = DIR_FS_CATALOG . 'zc_plugins/' . $sideboxInfo['plugin_details'] . '/'  . 'catalog/includes/modules/sideboxes/';
        if (file_exists($baseDir . $sideboxInfo['layout_box_name'])) {
            return $sideboxInfo['plugin_details'];
        }
        return false;
    }
}
