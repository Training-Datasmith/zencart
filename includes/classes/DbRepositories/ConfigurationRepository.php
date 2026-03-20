<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
namespace Zencart\Db_Repositories;

use Query_Factory;
/**
 * Native queryFactory-backed accessor for TABLE_CONFIGURATION.
 *
 * @since ZC v2.2.0
 */
class Configuration_Repository
{
    protected array $config_as_int_array = ['SECURITY_CODE_LENGTH'];
    protected array $keep_as_string_array = ['PRODUCTS_MANUFACTURERS_STATUS'];
    public function __construct(private readonly Query_Factory $db)
    {
    }
    /**
     * @since ZC v2.2.0
     */
    public function load_config_settings(): void
    {
        $configs = $this->db->Execute('SELECT configuration_key, configuration_value, configuration_group_id FROM ' . TABLE_CONFIGURATION);
        foreach ($configs as $config) {
            $key = strtoupper((string) $config['configuration_key']);
            $value = $config['configuration_value'];
            $group_id = (int) $config['configuration_group_id'];
            $convert_to_int = false;
            if (in_array($key, $this->config_as_int_array, true)) {
                $convert_to_int = true;
            } elseif (in_array($group_id, [2, 3], true) && !in_array($key, $this->keep_as_string_array, true)) {
                $convert_to_int = true;
            }
            if ($convert_to_int) {
                $value = (int) $value;
            }
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_by_key(string $configuration_key): ?array
    {
        $configuration_key = $this->db->prepare_input($configuration_key);
        $result = $this->db->Execute('SELECT configuration_id, configuration_key, configuration_value FROM ' . TABLE_CONFIGURATION . " WHERE configuration_key = '" . $configuration_key . "' LIMIT 1");
        if ($result->EOF) {
            return null;
        }
        return $result->fields;
    }
    /**
     * @since ZC v2.2.0
     */
    public function update_value_by_key(string $configuration_key, string $configuration_value): int
    {
        $configuration_key = $this->db->prepare_input($configuration_key);
        $configuration_value = $this->db->prepare_input($configuration_value);
        $this->db->Execute('UPDATE ' . TABLE_CONFIGURATION . " SET configuration_value = '" . $configuration_value . "'" . " WHERE configuration_key = '" . $configuration_key . "'");
        return $this->db->affected_rows();
    }
}