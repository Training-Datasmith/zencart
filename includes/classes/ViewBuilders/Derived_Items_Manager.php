<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\View_Builders;

use Zencart\File_System\File_System;
/**
 * @since ZC v1.5.8
 */
class Derived_Items_Manager
{
    /**
     * @since ZC v1.5.8
     */
    public function process(array $table_row, string $col_name, array $column_info): string
    {
        if (!isset($column_info['derivedItem'])) {
            return $table_row[$col_name];
        }
        return $this->process_derived_item($table_row, $col_name, $column_info);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_derived_item($table_row, string $col_name, array $column_info): string
    {
        $type = $column_info['derivedItem']['type'];
        switch ($type) {
            case 'local':
                return $this->{$column_info['derivedItem']['method']}($table_row, $col_name, $column_info);
            case 'closure':
                return $column_info['derivedItem']['method']($table_row, $col_name, $column_info);
        }
    }
    /**
     * @since ZC v1.5.8
     */
    protected function boolean_replace(array $table_row, string $col_name, array $column_info): string
    {
        $params = $column_info['derivedItem']['params'];
        $list_value = $table_row[$col_name];
        if ($list_value) {
            return $params['true'];
        }
        return $params['false'];
    }
    /**
     * @since ZC v1.5.8
     */
    protected function array_replace(array $table_row, string $col_name, array $column_info): string
    {
        $params = $column_info['derivedItem']['params'];
        $list_value = $table_row[$col_name];
        return $params[$list_value];
    }
    /**
     * @since ZC v1.5.8
     */
    protected function get_plugin_file_size(array $table_row, string $col_name, array $column_info): string
    {
        $file_path = DIR_FS_CATALOG . 'zc_plugins/' . $table_row['unique_key'] . '/';
        $fs = new File_System();
        return $fs->get_directory_size($file_path);
    }
    /**
     * @since ZC v2.1.0
     */
    protected function get_language_translation_for_name(array $table_row, string $col_name, array $column_info): string
    {
        return zen_lookup_admin_menu_language_override('plugin_name', $table_row['unique_key'], $table_row['name']);
    }
}