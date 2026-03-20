<?php

declare (strict_types=1);
/**
 * Load in any user functions
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: lat9 2025 Aug 16 Modified in v2.2.0 $
 */
use Zencart\File_System\File_System;
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
$extra_funcs_main = (new File_System())->list_files_from_directory_alpha_sorted(DIR_WS_FUNCTIONS . 'extra_functions/', '~^[^\._].*\.php$~i');
$extra_funcs_main = array_map(static fn($item): string => DIR_WS_FUNCTIONS . 'extra_functions/' . $item, $extra_funcs_main);
$context = IS_ADMIN_FLAG ? 'admin' : 'catalog';
$extra_funcs_plugins = [];
foreach ($installed_plugins as $plugin) {
    $path = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/' . $context . '/' . DIR_WS_FUNCTIONS . 'extra_functions/';
    $ef_plugin_file = (new File_System())->list_files_from_directory_alpha_sorted($path, '~^[^\._].*\.php$~i');
    $ef_plugin_file = array_map(static fn($item): string => $path . $item, $ef_plugin_file);
    $extra_funcs_plugins = array_merge($extra_funcs_plugins, $ef_plugin_file);
}
$extra_funcs_files = array_merge($extra_funcs_plugins, $extra_funcs_main);
foreach ($extra_funcs_files as $file) {
    if (!file_exists($file)) {
        continue;
    }
    include $file;
}
unset($extra_funcs_main, $extra_funcs_plugins, $extra_funcs_files, $ef_plugin_file, $file);