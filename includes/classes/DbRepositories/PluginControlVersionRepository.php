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
class Plugin_Control_Version_Repository
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
    public function get_by_unique_key(string $unique_key): array
    {
        $results = $this->db->Execute('SELECT * FROM ' . TABLE_PLUGIN_CONTROL_VERSIONS . " WHERE unique_key = '" . $this->db->prepare_input($unique_key) . "'");
        $versions = [];
        foreach ($results as $result) {
            $versions[] = $result;
        }
        return $versions;
    }
    /**
     * @since ZC v2.2.0
     */
    public function set_all_infs(int $infs): void
    {
        $this->db->Execute('UPDATE ' . TABLE_PLUGIN_CONTROL_VERSIONS . ' SET infs = ' . $infs);
    }
    /**
     * @since ZC v2.2.0
     */
    public function upsert_many(array $rows): void
    {
        foreach ($rows as $row) {
            $this->db->Execute('INSERT INTO ' . TABLE_PLUGIN_CONTROL_VERSIONS . ' (' . 'unique_key, author, version, zc_versions, infs' . ') VALUES (' . "'" . $this->db->prepare_input((string) $row['unique_key']) . "', " . "'" . $this->db->prepare_input((string) $row['author']) . "', " . "'" . $this->db->prepare_input((string) $row['version']) . "', " . "'" . $this->db->prepare_input((string) $row['zc_versions']) . "', " . (int) $row['infs'] . ') ON DUPLICATE KEY UPDATE ' . 'infs = VALUES(infs)');
        }
    }
    /**
     * @since ZC v2.2.0
     */
    public function delete_by_infs(int $infs): void
    {
        $this->db->Execute('DELETE FROM ' . TABLE_PLUGIN_CONTROL_VERSIONS . ' WHERE infs = ' . $infs);
    }
}