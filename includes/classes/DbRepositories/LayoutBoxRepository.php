<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
namespace Zencart\Db_Repositories;

use Query_Factory;
/**
 * @since ZC v2.2.0
 */
class Layout_Box_Repository
{
    public function __construct(private readonly Query_Factory $db)
    {
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_active_for_location(int $location, string $template, int $limit = 100): array
    {
        return $this->fetch_all('SELECT * FROM ' . TABLE_LAYOUT_BOXES . ' WHERE layout_box_location = ' . $location . ' AND layout_box_status = 1' . " AND layout_template = '" . $this->db->prepare_input($template) . "'" . ' ORDER BY layout_box_sort_order LIMIT ' . $limit);
    }
    /**
     * @since ZC v2.2.0
     */
    public function find_first_by_template_and_box_name(string $template, string $box_name): ?array
    {
        $result = $this->db->Execute('SELECT * FROM ' . TABLE_LAYOUT_BOXES . " WHERE layout_template = '" . $this->db->prepare_input($template) . "'" . " AND layout_box_name = '" . $this->db->prepare_input($box_name) . "'" . ' LIMIT 1');
        if ($result->EOF) {
            return null;
        }
        return $result->fields;
    }
    /**
     * @since ZC v2.2.0
     */
    public function insert(array $insert_values): int
    {
        $this->db->perform(TABLE_LAYOUT_BOXES, $this->build_sql_data_array($insert_values));
        return (int) $this->db->insert_ID();
    }
    /**
     * @since ZC v2.2.0
     */
    public function update_by_layout_id(int $layout_id, array $values): void
    {
        $this->db->perform(TABLE_LAYOUT_BOXES, $this->build_sql_data_array($values), 'UPDATE', 'layout_id = ' . $layout_id);
    }
    /**
     * @since ZC v2.2.0
     */
    public function delete_by_layout_id_and_name(int $layout_id, string $box_name): void
    {
        $this->db->Execute('DELETE FROM ' . TABLE_LAYOUT_BOXES . ' WHERE layout_id = ' . $layout_id . " AND layout_box_name = '" . $this->db->prepare_input($box_name) . "'");
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_by_template(string $template): array
    {
        return $this->fetch_all('SELECT * FROM ' . TABLE_LAYOUT_BOXES . " WHERE layout_template = '" . $this->db->prepare_input($template) . "'");
    }
    /**
     * @since ZC v2.2.0
     */
    public function update_by_template_and_box_name(string $template, string $box_name, array $values): void
    {
        $this->db->perform(TABLE_LAYOUT_BOXES, $this->build_sql_data_array($values), 'UPDATE', "layout_template = '" . $this->db->prepare_input($template) . "'" . " AND layout_box_name = '" . $this->db->prepare_input($box_name) . "'");
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_non_header_footer_by_template(string $template): array
    {
        return $this->fetch_all('SELECT * FROM ' . TABLE_LAYOUT_BOXES . " WHERE layout_template = '" . $this->db->prepare_input($template) . "'" . " AND layout_box_name NOT LIKE '%ezpages_bar'" . " AND layout_box_name NOT LIKE '%\\_header.php'" . " AND layout_box_name NOT LIKE '%\\_footer.php'" . ' ORDER BY layout_box_sort_order, layout_box_sort_order_single, layout_box_name');
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_by_template_and_name_like(string $template, string $pattern): array
    {
        return $this->fetch_all('SELECT * FROM ' . TABLE_LAYOUT_BOXES . " WHERE layout_template = '" . $this->db->prepare_input($template) . "'" . " AND layout_box_name LIKE '" . $this->db->prepare_input($pattern) . "'" . ' ORDER BY layout_box_sort_order_single, layout_box_name');
    }
    /**
     * @since ZC v2.2.0
     */
    public function update_plugin_details_by_prefix(string $plugin_key, string $version): void
    {
        $this->db->Execute('UPDATE ' . TABLE_LAYOUT_BOXES . " SET plugin_details = '" . $this->db->prepare_input($plugin_key . '/' . $version) . "'" . " WHERE plugin_details LIKE '" . $this->db->prepare_input($plugin_key . '/%') . "'");
    }
    /**
     * @since ZC v2.2.0
     */
    public function delete_by_plugin_details_prefix(string $plugin_key): void
    {
        $this->db->Execute('DELETE FROM ' . TABLE_LAYOUT_BOXES . " WHERE plugin_details LIKE '" . $this->db->prepare_input($plugin_key . '/%') . "'");
    }
    /**
     * @since ZC v2.2.0
     */
    protected function fetch_all(string $sql): array
    {
        $result = $this->db->Execute($sql);
        $rows = [];
        foreach ($result as $row) {
            $rows[] = $row;
        }
        return $rows;
    }
    /**
     * @since ZC v2.2.0
     */
    protected function build_sql_data_array(array $values): array
    {
        $sql_data_array = [];
        foreach ($values as $field => $value) {
            $type = 'string';
            if (is_int($value)) {
                $type = 'integer';
            } elseif (is_bool($value)) {
                $type = 'integer';
                $value = (int) $value;
            }
            $sql_data_array[] = ['fieldName' => $field, 'value' => $value, 'type' => $type];
        }
        return $sql_data_array;
    }
}