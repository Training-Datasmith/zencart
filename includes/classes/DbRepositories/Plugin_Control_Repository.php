<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  Modified in v2.2.0 $
 */
namespace Zencart\Db_Repositories;

use Query_Factory;
/**
 * @since ZC v2.2.0
 */
class Plugin_Control_Repository
{
    public function __construct(private ?Query_Factory $db = null)
    {
        if ($this->db === null) {
            global $db;
            $this->db = $db;
        }
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_installed_plugins(int $status): array
    {
        $results = $this->db->Execute('SELECT * FROM ' . TABLE_PLUGIN_CONTROL . ' WHERE status = ' . $status . ' ORDER BY name, unique_key');
        $plugin_list = [];
        foreach ($results as $result) {
            $row = $this->normalize_row($result);
            $plugin_list[$row['unique_key']] = $row;
        }
        return $plugin_list;
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_all(): array
    {
        $results = $this->db->Execute('SELECT * FROM ' . TABLE_PLUGIN_CONTROL);
        $plugin_list = [];
        foreach ($results as $result) {
            $row = $this->normalize_row($result);
            $plugin_list[$row['unique_key']] = $row;
        }
        return $plugin_list;
    }
    /**
     * @since ZC v2.2.0
     */
    public function set_all_infs(int $infs): void
    {
        $this->db->Execute('UPDATE ' . TABLE_PLUGIN_CONTROL . ' SET infs = ' . $infs);
    }
    /**
     * @since ZC v2.2.0
     */
    public function upsert_many(array $rows): void
    {
        foreach ($rows as $row) {
            $this->db->Execute('INSERT INTO ' . TABLE_PLUGIN_CONTROL . ' (' . 'unique_key, name, description, type, status, author, version, zc_versions, infs, zc_contrib_id' . ') VALUES (' . "'" . $this->db->prepare_input((string) $row['unique_key']) . "', " . "'" . $this->db->prepare_input((string) $row['name']) . "', " . "'" . $this->db->prepare_input((string) $row['description']) . "', " . "'" . $this->db->prepare_input((string) $row['type']) . "', " . (int) $row['status'] . ', ' . "'" . $this->db->prepare_input((string) $row['author']) . "', " . "'" . $this->db->prepare_input((string) $row['version']) . "', " . "'" . $this->db->prepare_input((string) $row['zc_versions']) . "', " . (int) $row['infs'] . ', ' . (int) $row['zc_contrib_id'] . ') ON DUPLICATE KEY UPDATE ' . 'name = VALUES(name), ' . 'description = VALUES(description), ' . 'infs = VALUES(infs), ' . 'author = VALUES(author), ' . 'zc_contrib_id = VALUES(zc_contrib_id)');
        }
    }
    /**
     * @since ZC v2.2.0
     */
    public function delete_by_infs(int $infs): void
    {
        $this->db->Execute('DELETE FROM ' . TABLE_PLUGIN_CONTROL . ' WHERE infs = ' . $infs);
    }
    /**
     * @since ZC v2.2.0
     */
    protected function normalize_row(array $row): array
    {
        $row['status'] = isset($row['status']) ? (int) $row['status'] : 0;
        $row['managed'] = isset($row['managed']) && (bool) $row['managed'];
        $row['zc_contrib_id'] = isset($row['zc_contrib_id']) ? (int) $row['zc_contrib_id'] : 0;
        $row['infs'] = isset($row['infs']) ? (int) $row['infs'] : 0;
        return $row;
    }
}