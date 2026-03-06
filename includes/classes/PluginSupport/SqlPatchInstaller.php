<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */

namespace Zencart\PluginSupport;

/**
 * @since ZC v1.5.7
 */
class SqlPatchInstaller
{
    /**
     * $sqlFunctionMap is a list of acceptable SQL
     * @var array
     */
    protected $sqlFunctionMap = [
        ['find' => 'DROP TABLE IF EXISTS ', 'length' => 21, 'method' => 'basic', 'tableParamsOffset' => 4],
        ['find' => 'DROP TABLE ', 'length' => 11, 'method' => 'basic', 'tableParamsOffset' => 2],
        ['find' => 'CREATE TABLE IF NOT EXISTS ', 'length' => 27, 'method' => 'basic', 'tableParamsOffset' => 5],
        ['find' => 'CREATE TABLE ', 'length' => 13, 'method' => 'basic', 'tableParamsOffset' => 2],
        ['find' => 'TRUNCATE TABLE ', 'length' => 15, 'method' => 'basic', 'tableParamsOffset' => 2],
        ['find' => 'REPLACE INTO ', 'length' => 13, 'method' => 'basic', 'tableParamsOffset' => 2],
        ['find' => 'INSERT INTO ', 'length' => 12, 'method' => 'basic', 'tableParamsOffset' => 2],
        ['find' => 'INSERT IGNORE INTO ', 'length' => 19, 'method' => 'basic', 'tableParamsOffset' => 3],
        ['find' => 'ALTER TABLE ', 'length' => 12, 'method' => 'basic', 'tableParamsOffset' => 2],
        ['find' => 'RENAME TABLE ', 'length' => 13, 'method' => 'renameTable', 'tableParamsOffset' => 2],
        ['find' => 'UPDATE ', 'length' => 7, 'method' => 'basic', 'tableParamsOffset' => 1],
        ['find' => 'DELETE FROM ', 'length' => 12, 'method' => 'basic', 'tableParamsOffset' => 2],
        ['find' => 'DROP INDEX ', 'length' => 11, 'method' => 'index', 'tableParamsOffset' => 2],
        ['find' => 'CREATE INDEX ', 'length' => 13, 'method' => 'index', 'tableParamsOffset' => 2],
        ['find' => 'SELECT ', 'length' => 7, 'method' => 'select', 'tableParamsOffset' => 1],
    ];

    /**
     * @param object $dbConn
     * @param object $errorContainer
     */
    public function __construct(
        /**
         * $dbConn is a database object
         */
        protected $dbConn,
        /**
         * $errorContainer is a PluginErrorContainer object
         */
        protected $errorContainer
    ) {
    }

    /**
     * @since ZC v1.5.7
     * @return mixed[]
     */
    public function parse($lines): array
    {
        $builtLines = $this->getFullLines($lines);
        $paramLines = [];
        foreach ($builtLines as $line) {
            $paramLines[] = $this->processLine($line);
        }
        return $paramLines;
    }

    /**
     * @since ZC v1.5.7
     */
    public function executePatchSql($paramLines): void
    {
        $this->dbConn->dieOnErrors = false;
        foreach ($paramLines as $line) {
            $sql = implode(' ', $line) . ';';
            $this->dbConn->execute($sql);
            if ($this->dbConn->error_number !== 0) {
                $this->errorContainer->addError(0, ERROR_SQL_PATCH . $this->dbConn->error_text . '<br>' . $sql, true);
                break;
            }
        }
        $this->dbConn->dieOnErrors = true;
    }

    /**
     * @since ZC v1.5.7
     * @return string[]
     */
    protected function getFullLines($lines): array
    {
        $fullLine = '';
        $builtLines = [];
        foreach ($lines as $line) {
            $line = str_replace('`', '', trim((string) $line));
            $fullLine .= ' ' . $line;
            if (str_ends_with($line, ';')) {
                $builtLines[] = ltrim($fullLine);
                $fullLine = '';
            }
        }
        return $builtLines;
    }

    /**
     * @since ZC v1.5.7
     */
    protected function processLine(string $line)
    {
        $params = explode(' ', (str_ends_with($line, ';')) ? substr($line, 0, strlen($line) - 1) : $line);
        $type = $this->findSqlLineType(strtoupper($line));

        if (count($type) === 0) {
            $this->errorContainer->addError(0, ERROR_NOT_FOUND_IN_SQL_FUNCTIONS_MAP. $line, true);
            return [];
        }
        $method = 'processLine' . ucfirst((string) $type['method']);
        $newParams = $this->$method($params, $type);
        /*
         * if empty the line could not be correctly parsed
         */
        if (empty($newParams)) {
            $this->errorContainer->addError(0, ERROR_INVALID_SYNTAX . $line, true);
        }
        return $newParams;
    }

    /**
     * @since ZC v1.5.7
     */
    protected function findSqlLineType($line)
    {
        $result = [];
        foreach ($this->sqlFunctionMap as $entry) {
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
    protected function processLineBasic(array $params, array $typeEntry): array
    {
        $params[$typeEntry['tableParamsOffset']] = DB_PREFIX . $params[$typeEntry['tableParamsOffset']];
        return $params;
    }

    /**
     * @since ZC v1.5.7
     * @return mixed[]
     */
    protected function processLineSelect(array $params, $typeEntry): array
    {
        $fromKey = array_search('FROM', $params);
        if ($fromKey === false) {
            return [];
        }
        $params[$fromKey + 1] = DB_PREFIX . $params[$fromKey + 1];
        $joinKeys = array_keys($params, 'JOIN');
        foreach ($joinKeys as $fromKey) {
            $params[$fromKey + 1] = DB_PREFIX . $params[$fromKey + 1];
        }
        return $params;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function processLineIndex(array $params, $typeEntry): array
    {
        $fromKey = array_search('ON', $params);
        if ($fromKey === false) {
            return [];
        }
        $params[$fromKey + 1] = DB_PREFIX . $params[$fromKey + 1];
        return $params;
    }

    /**
     * @since ZC v1.5.8
     */
    protected function processLineRenameTable(array $params, array $typeEntry): array
    {
        $params[$typeEntry['tableParamsOffset']] = DB_PREFIX . $params[$typeEntry['tableParamsOffset']];
        $params[$typeEntry['tableParamsOffset'] + 2] = DB_PREFIX . $params[$typeEntry['tableParamsOffset'] + 2];
        return $params;
    }
}
