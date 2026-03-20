<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Plugin_Support;

/**
 * @since ZC v1.5.7
 */
class Sql_Patch_Installer
{
    /**
     * $sqlFunctionMap is a list of acceptable SQL
     * @var array
     */
    protected $sql_function_map = [['find' => 'DROP TABLE IF EXISTS ', 'length' => 21, 'method' => 'basic', 'tableParamsOffset' => 4], ['find' => 'DROP TABLE ', 'length' => 11, 'method' => 'basic', 'tableParamsOffset' => 2], ['find' => 'CREATE TABLE IF NOT EXISTS ', 'length' => 27, 'method' => 'basic', 'tableParamsOffset' => 5], ['find' => 'CREATE TABLE ', 'length' => 13, 'method' => 'basic', 'tableParamsOffset' => 2], ['find' => 'TRUNCATE TABLE ', 'length' => 15, 'method' => 'basic', 'tableParamsOffset' => 2], ['find' => 'REPLACE INTO ', 'length' => 13, 'method' => 'basic', 'tableParamsOffset' => 2], ['find' => 'INSERT INTO ', 'length' => 12, 'method' => 'basic', 'tableParamsOffset' => 2], ['find' => 'INSERT IGNORE INTO ', 'length' => 19, 'method' => 'basic', 'tableParamsOffset' => 3], ['find' => 'ALTER TABLE ', 'length' => 12, 'method' => 'basic', 'tableParamsOffset' => 2], ['find' => 'RENAME TABLE ', 'length' => 13, 'method' => 'renameTable', 'tableParamsOffset' => 2], ['find' => 'UPDATE ', 'length' => 7, 'method' => 'basic', 'tableParamsOffset' => 1], ['find' => 'DELETE FROM ', 'length' => 12, 'method' => 'basic', 'tableParamsOffset' => 2], ['find' => 'DROP INDEX ', 'length' => 11, 'method' => 'index', 'tableParamsOffset' => 2], ['find' => 'CREATE INDEX ', 'length' => 13, 'method' => 'index', 'tableParamsOffset' => 2], ['find' => 'SELECT ', 'length' => 7, 'method' => 'select', 'tableParamsOffset' => 1]];
    /**
     * @param object $dbConn
     * @param object $errorContainer
     */
    public function __construct(
        /**
         * $dbConn is a database object
         */
        protected $db_conn,
        /**
         * $errorContainer is a PluginErrorContainer object
         */
        protected $error_container
    )
    {
    }
    /**
     * @since ZC v1.5.7
     * @return mixed[]
     */
    public function parse($lines): array
    {
        $built_lines = $this->get_full_lines($lines);
        $param_lines = [];
        foreach ($built_lines as $line) {
            $param_lines[] = $this->process_line($line);
        }
        return $param_lines;
    }
    /**
     * @since ZC v1.5.7
     */
    public function execute_patch_sql($param_lines): void
    {
        $this->db_conn->die_on_errors = false;
        foreach ($param_lines as $line) {
            $sql = implode(' ', $line) . ';';
            $this->db_conn->execute($sql);
            if ($this->db_conn->error_number !== 0) {
                $this->error_container->add_error(0, ERROR_SQL_PATCH . $this->db_conn->error_text . '<br>' . $sql, true);
                break;
            }
        }
        $this->db_conn->die_on_errors = true;
    }
    /**
     * @since ZC v1.5.7
     * @return string[]
     */
    protected function get_full_lines($lines): array
    {
        $full_line = '';
        $built_lines = [];
        foreach ($lines as $line) {
            $line = str_replace('`', '', trim((string) $line));
            $full_line .= ' ' . $line;
            if (str_ends_with($line, ';')) {
                $built_lines[] = ltrim($full_line);
                $full_line = '';
            }
        }
        return $built_lines;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_line(string $line)
    {
        $params = explode(' ', str_ends_with($line, ';') ? substr($line, 0, strlen($line) - 1) : $line);
        $type = $this->find_sql_line_type(strtoupper($line));
        if (count($type) === 0) {
            $this->error_container->add_error(0, ERROR_NOT_FOUND_IN_SQL_FUNCTIONS_MAP . $line, true);
            return [];
        }
        $method = 'processLine' . ucfirst((string) $type['method']);
        $new_params = $this->{$method}($params, $type);
        /*
         * if empty the line could not be correctly parsed
         */
        if (empty($new_params)) {
            $this->error_container->add_error(0, ERROR_INVALID_SYNTAX . $line, true);
        }
        return $new_params;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function find_sql_line_type($line)
    {
        $result = [];
        foreach ($this->sql_function_map as $entry) {
            if (substr((string) $line, 0, $entry['length']) != $entry['find']) {
                continue;
            }
            $result = $entry;
            break;
        }
        return $result;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_line_basic(array $params, array $type_entry): array
    {
        $params[$type_entry['tableParamsOffset']] = DB_PREFIX . $params[$type_entry['tableParamsOffset']];
        return $params;
    }
    /**
     * @since ZC v1.5.7
     * @return mixed[]
     */
    protected function process_line_select(array $params, $type_entry): array
    {
        $from_key = array_search('FROM', $params);
        if ($from_key === false) {
            return [];
        }
        $params[$from_key + 1] = DB_PREFIX . $params[$from_key + 1];
        $join_keys = array_keys($params, 'JOIN');
        foreach ($join_keys as $from_key) {
            $params[$from_key + 1] = DB_PREFIX . $params[$from_key + 1];
        }
        return $params;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_line_index(array $params, $type_entry): array
    {
        $from_key = array_search('ON', $params);
        if ($from_key === false) {
            return [];
        }
        $params[$from_key + 1] = DB_PREFIX . $params[$from_key + 1];
        return $params;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_line_rename_table(array $params, array $type_entry): array
    {
        $params[$type_entry['tableParamsOffset']] = DB_PREFIX . $params[$type_entry['tableParamsOffset']];
        $params[$type_entry['tableParamsOffset'] + 2] = DB_PREFIX . $params[$type_entry['tableParamsOffset'] + 2];
        return $params;
    }
}