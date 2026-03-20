<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 *
 */
namespace Zencart\Plugin_Support;

use Query_Factory;
use Query_Factory_Result;
use Zencart\Db_Repositories\Layout_Box_Repository;
/**
 * @since ZC v2.0.1
 */
trait Scripted_Install_Helpers
{
    protected Query_Factory $db_conn;
    /**
     * Get details of current configuration record entry, false if not found.
     * Optional: when $only_check_existence is true, will simply return true/false.
     * @since ZC v2.0.1
     */
    protected function get_configuration_key_details(string $key_name, bool $only_check_existence = false): array|bool
    {
        $sql = 'SELECT * FROM ' . TABLE_CONFIGURATION . " WHERE configuration_key = '" . $this->db_conn->prepare_input($key_name) . "'";
        $result = $this->execute_installer_select_query($sql, 1);
        // false if not found, or if existence-check fails
        if ($only_check_existence || $result->EOF) {
            return !$result->EOF;
        }
        return $result->fields;
    }
    /**
     * @since ZC v2.0.1
     */
    protected function add_configuration_key(string $key_name, array $properties): int
    {
        $exists = $this->get_configuration_key_details($key_name, true);
        if ($exists !== false) {
            return 0;
        }
        $fields = [
            //'configuration_key', // VARCHAR(180)
            'configuration_title',
            'configuration_value',
            'configuration_description',
            'configuration_group_id',
            'sort_order',
            // INT(5) default NULL
            'use_function',
            // TEXT default NULL
            'set_function',
            // TEXT default NULL
            'val_function',
        ];
        $sql_data_array = [];
        $sql_data_array[] = ['fieldName' => 'configuration_key', 'value' => $key_name, 'type' => 'string'];
        foreach ($fields as $field) {
            if (isset($properties[$field])) {
                $type = 'string';
                if (in_array($field, ['configuration_group_id', 'sort_order'])) {
                    $type = 'integer';
                }
                $sql_data_array[] = ['fieldName' => $field, 'value' => $properties[$field], 'type' => $type];
            }
        }
        $sql_data_array[] = ['fieldName' => 'date_added', 'value' => 'NOW()', 'type' => 'passthru'];
        $this->execute_installer_db_perform(TABLE_CONFIGURATION, $sql_data_array);
        $insert_id = $this->db_conn->insert_ID();
        $sql_data_array[] = ['fieldName' => 'configuration_key_id', 'value' => $insert_id, 'type' => 'integer'];
        zen_record_admin_activity('Added configuration record: ' . print_r($sql_data_array, true), 'warning');
        return $insert_id;
    }
    /**
     * @since ZC v2.0.1
     */
    protected function update_configuration_key(string $key_name, array $properties): int
    {
        $fields = [
            'configuration_title',
            'configuration_value',
            'configuration_description',
            'configuration_group_id',
            'sort_order',
            // INT(5) default NULL
            'use_function',
            // TEXT default NULL
            'set_function',
            // TEXT default NULL
            'val_function',
        ];
        $sql_data_array = [];
        foreach ($fields as $field) {
            if (isset($properties[$field])) {
                $type = 'string';
                if (in_array($field, ['configuration_group_id', 'sort_order'])) {
                    $type = 'integer';
                }
                $sql_data_array[] = ['fieldName' => $field, 'value' => $properties[$field], 'type' => $type];
            }
        }
        $sql_data_array[] = ['fieldName' => 'last_modified', 'value' => 'now()', 'type' => 'passthru'];
        $this->execute_installer_db_perform(TABLE_CONFIGURATION, $sql_data_array, 'UPDATE', "configuration_key = '" . $this->db_conn->prepare_input($key_name) . "'");
        $rows = $this->db_conn->affected_rows();
        $sql_data_array[] = ['fieldName' => 'configuration_key', 'value' => $key_name, 'type' => 'string'];
        zen_record_admin_activity('Updated configuration record: ' . print_r($sql_data_array, true), 'warning');
        return $rows;
    }
    /**
     * @since ZC v2.0.1
     */
    protected function delete_configuration_keys(array $key_names): int
    {
        if (empty($key_names)) {
            return 0;
        }
        $db = $this->db_conn;
        $keys_list = implode("','", array_map(static fn($val) => $db->prepare_input($val), $key_names));
        $sql = 'DELETE FROM ' . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . $keys_list . "')";
        $this->execute_installer_select_query($sql);
        $rows = $this->db_conn->affected_rows();
        zen_record_admin_activity('Deleted configuration record(s): ' . $keys_list . ", {$rows} rows affected.", 'warning');
        return $rows;
    }
    /**
     * @since ZC v2.1.0
     */
    protected function get_or_create_config_group_id(string $config_group_title, string $config_group_description, ?int $sort_order = 1): int
    {
        $config_group_title = $this->db_conn->prepare_input($config_group_title);
        $config_group_description = $this->db_conn->prepare_input($config_group_description);
        $sort_order ??= 0;
        $sql = 'SELECT configuration_group_id
               FROM ' . TABLE_CONFIGURATION_GROUP . "\n              WHERE configuration_group_title = '{$config_group_title}'\n              LIMIT 1";
        $check = $this->execute_installer_select_query($sql);
        if (!$check->EOF) {
            return (int) $check->fields['configuration_group_id'];
        }
        $sql = 'INSERT INTO ' . TABLE_CONFIGURATION_GROUP . "\n                (configuration_group_title, configuration_group_description, sort_order, visible)\n             VALUES\n                ('{$config_group_title}', '{$config_group_description}', {$sort_order}, 1)";
        $this->execute_installer_sql($sql);
        $sql = 'SELECT configuration_group_id FROM ' . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_title = '{$config_group_title}' LIMIT 1";
        $result = $this->execute_installer_select_query($sql);
        $cgi = (int) $result->fields['configuration_group_id'];
        if (empty($sort_order)) {
            $sql = 'UPDATE ' . TABLE_CONFIGURATION_GROUP . " SET sort_order = {$cgi} WHERE configuration_group_id = {$cgi} LIMIT 1";
            $this->execute_installer_sql($sql);
        }
        return $cgi;
    }
    /**
     * @since ZC v2.0.1
     */
    protected function add_configuration_group(array $properties): int
    {
        $exists = $this->get_configuration_key_details($this->db_conn->prepare_input($properties['configuration_group_title']));
        if ($exists !== false) {
            return (int) $exists['configuration_group_id'];
        }
        $fields = [
            'configuration_group_title',
            // varchar(64)
            'configuration_group_description',
            // varchar(255)
            'sort_order',
            // int (will be made to match auto-increment id if not specified)
            'visible',
        ];
        $sql_data_array = [];
        foreach ($fields as $field) {
            if (isset($properties[$field])) {
                $type = 'string';
                if (in_array($field, ['sort_order', 'visible'])) {
                    $type = 'integer';
                }
                $sql_data_array[] = ['fieldName' => $field, 'value' => $properties[$field], 'type' => $type];
            }
        }
        $this->execute_installer_db_perform(TABLE_CONFIGURATION_GROUP, $sql_data_array);
        $insert_id = $this->db_conn->insert_ID();
        // update array for subsequent logging
        $sql_data_array[] = ['fieldName' => 'configuration_group_id', 'value' => $insert_id, 'type' => 'integer'];
        // manually set sort order if none was provided:
        if (empty($properties['sort_order'])) {
            $sql = 'UPDATE ' . TABLE_CONFIGURATION_GROUP . " SET sort_order = {$insert_id} WHERE configuration_group_id = {$insert_id} LIMIT 1";
            $this->execute_installer_sql($sql);
            $sql_data_array[] = ['fieldName' => 'sort_order', 'value' => $insert_id, 'type' => 'integer'];
        }
        zen_record_admin_activity('Configuration Group added: ' . print_r($sql_data_array, true), 'warning');
        return $insert_id;
    }
    /**
     * @since ZC v2.0.1
     */
    protected function update_configuration_group(int $group_id, array $properties): int
    {
        $fields = [
            'configuration_group_title',
            // varchar(64) NOT NULL default ''
            'configuration_group_description',
            // varchar(255) NOT NULL default ''
            'sort_order',
            // int(5) default NULL
            'visible',
        ];
        $sql_data_array = [];
        foreach ($fields as $field) {
            if (isset($properties[$field])) {
                $type = 'string';
                if (in_array($field, ['sort_order', 'visible'])) {
                    $type = 'integer';
                }
                $sql_data_array[] = ['fieldName' => $field, 'value' => $properties[$field], 'type' => $type];
            }
        }
        $this->execute_installer_db_perform(TABLE_CONFIGURATION_GROUP, $sql_data_array, 'UPDATE', 'configuration_group_id = ' . $group_id);
        $rows = $this->db_conn->affected_rows();
        $sql_data_array[] = ['fieldName' => 'configuration_group_id', 'value' => $group_id, 'type' => 'integer'];
        zen_record_admin_activity('Updated configuration group: ' . print_r($sql_data_array, true) . ", {$rows} rows affected.", 'warning');
        return $rows;
    }
    /**
     * @since ZC v2.0.1
     */
    protected function delete_configuration_group(int|string $group, bool $cascade_delete_keys_too = false): int
    {
        $rows = 0;
        $sql = 'SELECT * FROM ' . TABLE_CONFIGURATION_GROUP;
        if (is_numeric($group)) {
            $sql .= ' WHERE configuration_group_id = ' . (int) $group;
        } else {
            $sql .= " WHERE configuration_group_title = '" . $this->db_conn->prepare_input($group) . "'";
        }
        $result = $this->execute_installer_select_query($sql);
        $cgi = (int) ($result->fields['configuration_group_id'] ?? 0);
        if ($cascade_delete_keys_too) {
            $sql = 'DELETE FROM ' . TABLE_CONFIGURATION . " WHERE configuration_group_id = {$cgi}";
            $this->execute_installer_sql($sql);
            $rows += $this->db_conn->affected_rows();
        }
        $sql = 'DELETE FROM ' . TABLE_CONFIGURATION_GROUP . ' WHERE configuration_group_id = ' . $cgi;
        $this->execute_installer_sql($sql);
        $rows += $this->db_conn->affected_rows();
        zen_record_admin_activity("Deleted configuration group ID: '{$group}'; {$rows} rows affected.", 'warning');
        return $rows;
    }
    /**
     * @since ZC v2.0.1
     */
    protected function get_configuration_group_details(int|string $group, bool $only_check_existence = false): array|bool
    {
        $sql = 'SELECT * FROM ' . TABLE_CONFIGURATION_GROUP;
        if (is_numeric($group)) {
            $sql .= ' WHERE configuration_group_id = ' . (int) $group;
        } else {
            $sql .= " WHERE configuration_group_title = '" . $this->db_conn->prepare_input($group) . "'";
        }
        $result = $this->execute_installer_select_query($sql);
        // false if not found, or if existence-check fails
        if ($only_check_existence || $result->EOF) {
            return !$result->EOF;
        }
        return $result->fields;
    }
    /**
     * @since ZC v2.1.0
     */
    protected function execute_installer_select_query(string $sql, ?int $limit = null): bool|Query_Factory_Result
    {
        $this->db_conn->die_on_errors = false;
        $result = $this->db_conn->Execute($sql, $limit);
        if ($this->db_conn->error_number !== 0) {
            $this->error_container->add_error(0, $this->db_conn->error_text, true, PLUGIN_INSTALL_SQL_FAILURE);
            return false;
        }
        $this->db_conn->die_on_errors = true;
        return $result;
    }
    /**
     * @since ZC v2.1.0
     */
    protected function execute_installer_db_perform(string $table, array $sql_data_array, $perform_type = 'INSERT', string $where_condition = '', $debug = false): bool
    {
        $this->db_conn->die_on_errors = false;
        $this->db_conn->perform($table, $sql_data_array, $perform_type, $where_condition, $debug);
        if ($this->db_conn->error_number !== 0) {
            $this->error_container->add_error(0, $this->db_conn->error_text, true, PLUGIN_INSTALL_SQL_FAILURE);
            return false;
        }
        $this->db_conn->die_on_errors = true;
        return true;
    }
    // -----
    // This method provides the means to update various database fields
    // that are managed by core Zen Cart processes on the update of an encapsulated
    // plugin.
    //
    /**
     * @since ZC v2.2.0
     */
    private function update_zen_core_db_fields(string $old_version): void
    {
        // -----
        // Update any layout_boxes table entries that reference the current
        // plugin, ensuring that the 'plugin_details' field contains the updated
        // plugin version number.
        //
        $layout_box_repository = new Layout_Box_Repository($this->db_conn);
        $layout_box_repository->update_plugin_details_by_prefix($this->plugin_key, $this->version);
    }
    // -----
    // This method provides the means to update various database fields
    // that are managed by core Zen Cart processes on the uninstall of an encapsulated
    // plugin.
    //
    /**
     * @since ZC v2.2.0
     */
    private function uninstall_zen_core_db_fields(): void
    {
        // -----
        // Remove any entries in the layout_boxes table that reference this now
        // uninstalled plugin.
        //
        $layout_box_repository = new Layout_Box_Repository($this->db_conn);
        $layout_box_repository->delete_by_plugin_details_prefix($this->plugin_key);
        // -----
        // If a plugin includes order_total, payment or shipping modules, any
        // modules that are currently "installed" must be removed from the
        // respective configuration setting (set by the admin's Modules processing)
        // or various PHP errors/warnings could occur.
        //
        zen_update_modules_cache();
    }
}