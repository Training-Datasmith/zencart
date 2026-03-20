<?php

declare (strict_types=1);
/**
 * Memoization cache for MySQL SELECT queries
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Created by Data-Diggers.com http://www.data-diggers.com/
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
/**
 * QueryCache memoization cache for SELECT queries
 * @since ZC v1.5.1
 */
class Query_Cache
{
    protected $queries = [];
    /**
     * Cache SELECT statement query resources in application memory
     * returns TRUE if and only if query has been stored in cache
     * @param string $query query string, used as a key
     * @param mysqli_result $valueToStore result from mysqli_query
     * @since ZC v1.5.1
     */
    public function cache(string $query, $value_to_store): bool
    {
        if ($this->is_select_statement($query) === true) {
            $this->queries[$query] = $value_to_store;
        } else {
            return false;
        }
        return true;
    }
    /**
     * @return mixed
     * @since ZC v1.5.1
     */
    public function get_from_cache(string $query)
    {
        $ret = $this->queries[$query];
        mysqli_data_seek($ret, 0);
        return $ret;
    }
    /**
     * @param string $query used as a cache key
     * @since ZC v1.5.1
     */
    public function in_cache(string $query): bool
    {
        return isset($this->queries[$query]) && $this->queries[$query] instanceof mysqli_result;
    }
    /**
     * ensure the query is a SELECT query
     * @since ZC v1.5.1
     */
    protected function is_select_statement(string $q): bool
    {
        return 0 === stripos($q, 'SELECT');
    }
    /**
     * Remove query from cache. Pass ALL to reset entire cache
     * @return bool
     * @since ZC v1.5.3
     */
    public function reset(string $query)
    {
        if ('ALL' == $query) {
            $this->queries = [];
            return false;
        }
        unset($this->queries[$query]);
    }
}