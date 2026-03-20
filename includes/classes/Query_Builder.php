<?php

declare (strict_types=1);
/**
 * Class QueryBuilder
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
/**
 * Class QueryBuilder
 */
namespace Zencart\Query_Builder;

/**
 * @since ZC v1.5.7
 */
class Query_Builder extends \base
{
    /**
     * query parts
     *
     * @var array
     */
    protected $parts;
    /**
     * query
     *
     * @var array
     */
    protected $query;
    public function __construct(
        /**
         * @var
         */
        protected $db_conn,
        array $listing_query = []
    )
    {
        $this->parts = null;
        if (count($listing_query) > 0) {
            $this->init_parts($listing_query);
        }
    }
    /**
     * @since ZC v1.5.7
     */
    public function init_parts(array $listing_query): void
    {
        $this->notify('NOTIFY_QUERYBUILDER_INIT_START');
        $this->parts['bindVars'] = issetor_array($listing_query, 'bindVars', []);
        $this->parts['selectList'] = issetor_array($listing_query, 'selectList', []);
        $this->parts['orderBys'] = issetor_array($listing_query, 'orderBys', []);
        $this->parts['groupBys'] = issetor_array($listing_query, 'groupBys', []);
        $this->parts['filters'] = issetor_array($listing_query, 'filters', []);
        $this->parts['derivedItems'] = issetor_array($listing_query, 'derivedItems', []);
        $this->parts['joinTables'] = issetor_array($listing_query, 'joinTables', []);
        $this->parts['whereClauses'] = issetor_array($listing_query, 'whereClauses', []);
        $this->parts['mainTableName'] = TABLE_PRODUCTS;
        $this->parts['countField'] = 'products_id';
        if (isset($listing_query['mainTable'])) {
            $this->parts['mainTableName'] = $listing_query['mainTable']['table'];
            $this->parts['countField'] = $listing_query['mainTable']['countField'];
        }
        $this->notify('NOTIFY_QUERYBUILDER_INIT_END');
    }
    /**
     * process query
     *
     * @since ZC v1.5.7
     */
    public function process_query($listing_query): void
    {
        if (!isset($this->parts)) {
            $this->init_parts($listing_query);
        }
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSQUERY_START');
        $this->query['select'] = 'SELECT ' . (issetor_array($listing_query, 'isDistinct', false) ? ' DISTINCT ' : '');
        if (count($this->parts['groupBys']) == 0) {
            $this->query['select'] .= $this->parts['mainTableName'] . '.*';
        }
        $this->process_select_list();
        $this->pre_process_joins();
        $this->query['joins'] = '';
        $this->query['table'] = ' FROM ';
        $this->process_joins();
        $this->query['table'] .= $this->parts['mainTableName'] . ' AS ' . $this->parts['mainTableName'] . ' ';
        $this->process_where_clause();
        $this->process_group_bys();
        $this->process_order_bys();
        $this->set_final_query($listing_query);
        $this->process_bind_vars();
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSQUERY_END');
    }
    /**
     * @since ZC v1.5.7
     */
    protected function set_final_query($listing_query)
    {
        $this->notify('NOTIFY_QUERYBUILDER_SETFINALQUERY_START');
        $this->query['mainSql'] = $this->query['select'] . $this->query['table'] . $this->query['joins'] . $this->query['where'] . $this->query['groupBy'] . $this->query['orderBy'];
        if (!isset($this->query['countSql'])) {
            $this->query['countSql'] = 'SELECT COUNT(' . (issetor_array($listing_query, 'isDistinct', false) ? 'DISTINCT ' : '') . $this->parts['mainTableName'] . '.' . $this->parts['countField'] . ')
                                 AS total ' . $this->query['table'] . $this->query['joins'] . $this->query['where'];
        }
        $this->notify('NOTIFY_QUERYBUILDER_SETFINALQUERY_END');
    }
    /**
     * preprocess joins
     *
     * @since ZC v1.5.7
     */
    protected function pre_process_joins()
    {
        $this->notify('NOTIFY_QUERYBUILDER_PREPROCESSJOINS_START');
        if (count($this->parts['joinTables']) == 0) {
            return;
        }
        $this->query['joins'] = '';
        $this->notify('NOTIFY_QUERYBUILDER_PREPROCESSJOINS_END');
    }
    /**
     * process joins
     *
     * @since ZC v1.5.7
     */
    protected function process_joins()
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSJOINS_START');
        if (count($this->parts['joinTables']) == 0) {
            return;
        }
        foreach ($this->parts['joinTables'] as $join_table) {
            $this->query['joins'] .= strtoupper((string) $join_table['type']) . ' JOIN ' . $join_table['table'] . ' AS ' . $join_table['table'];
            $this->process_join_fkey_field($join_table);
            $this->process_join_custom_and($join_table);
            $this->process_join_add_columns($join_table);
        }
        $this->query['table'] .= '(';
        $this->query['joins'] .= ')';
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSJOINS_END');
    }
    /**
     * process join custom adds
     *
     * @param $joinTable
     * @since ZC v1.5.7
     */
    protected function process_join_custom_and(array $join_table)
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSJOINSCUSTOMAND_START');
        if (isset($join_table['customAnd'])) {
            $this->query['joins'] .= ' ' . $join_table['customAnd'] . ' ';
        }
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSJOINSCUSTOMAND_END');
    }
    /**
     * process join add columns
     *
     * @param $joinTable
     * @since ZC v1.5.7
     */
    protected function process_join_add_columns(array $join_table)
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSJOINADDCOLUMN_START');
        if (isset($join_table['addColumns']) && $join_table['addColumns']) {
            $this->query['select'] .= ', ' . $join_table['table'] . '.*';
        }
        if (isset($join_table['selectColumns'])) {
            foreach ($join_table['selectColumns'] as $column) {
                $this->query['select'] .= ', ' . $join_table['table'] . '.' . $column;
            }
        }
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSJOINADDCOLUMN_ENDT');
    }
    /**
     * process join foreign keys
     *
     * @param $joinTable
     * @since ZC v1.5.7
     */
    protected function process_join_fkey_field(array $join_table)
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSJOINFKEYFIELD_START');
        $fkey_field_left = $this->parts['mainTableName'] . '.' . $this->parts['countField'];
        $fkey_field_right = $join_table['table'] . '.' . $this->parts['countField'];
        if (!isset($join_table['fkeyFieldLeft'])) {
            $this->query['joins'] .= ' ON ' . $fkey_field_left . ' = ' . $fkey_field_right . ' ';
            return;
        }
        $fkey_field_left = $this->parts['mainTableName'] . '.' . $join_table['fkeyFieldLeft'];
        if (isset($join_table['fkeyTable'])) {
            $fkey_field_left = constant($join_table['fkeyTable']) . '.' . $join_table['fkeyFieldLeft'];
        }
        $fkey_field_right = $join_table['table'] . '.' . $join_table['fkeyFieldLeft'];
        if (isset($join_table['fkeyFieldRight'])) {
            $fkey_field_right = $join_table['table'] . '.' . $join_table['fkeyFieldRight'];
        }
        $this->query['joins'] .= ' ON ' . $fkey_field_left . ' = ' . $fkey_field_right . ' ';
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSJOINFKEYFIELD_END');
    }
    /**
     * process where clauses
     * @since ZC v1.5.7
     */
    protected function process_where_clause()
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSWHERECLAUSE_START');
        $this->query['where'] = ' WHERE 1';
        if (count($this->parts['whereClauses']) == 0) {
            return;
        }
        foreach ($this->parts['whereClauses'] as $where_clause) {
            if (isset($where_clause['custom'])) {
                $this->query['where'] .= ' ' . trim($where_clause['custom']) . ' ';
                continue;
            }
            $this->process_where_clause_test($where_clause);
        }
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSWHERECLAUSE_END');
    }
    /**
     * process where clauses test
     *
     * @param $whereClause
     * @since ZC v1.5.7
     */
    protected function process_where_clause_test(array $where_clause)
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSWHERECLAUSETEST_START');
        if (!isset($where_clause['test'])) {
            $where_clause['test'] = '=';
        }
        if (!isset($where_clause['type'])) {
            $where_clause['type'] = 'AND';
        }
        $default = ' ' . $where_clause['test'] . ' ' . $where_clause['value'];
        $hash_map = ['IN' => ' IN ( ' . $where_clause['value'] . ' ) ', 'LIKE' => ' LIKE ' . $where_clause['value'] . ' '];
        $add_test = $hash_map[strtoupper($where_clause['test'])] ?? $default;
        $this->query['where'] .= ' ' . $where_clause['type'] . ' ' . $where_clause['table'] . '.' . $where_clause['field'] . $add_test;
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSWHERECLAUSETEST_END');
    }
    /**
     * process orderBy clauses
     * @since ZC v1.5.7
     */
    protected function process_order_bys()
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSORDERBYS_START');
        $this->query['orderBy'] = '';
        if (count($this->parts['orderBys']) == 0) {
            return;
        }
        $this->query['orderBy'] = ' ORDER BY ';
        foreach ($this->parts['orderBys'] as $order_by) {
            $result = $this->process_order_by_entry($order_by);
            if ($result) {
                continue;
            }
        }
        if (substr($this->query['orderBy'], strlen($this->query['orderBy']) - 2) == ', ') {
            $this->query['orderBy'] = substr($this->query['orderBy'], 0, strlen($this->query['orderBy']) - 2) . ' ';
        }
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSORDERBYS_END');
    }
    /**
     * process orderBy clauses
     * @since ZC v1.5.7
     */
    protected function process_group_bys()
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSGROUPBYS_START');
        $this->query['groupBy'] = '';
        if (count($this->parts['groupBys']) == 0) {
            return;
        }
        $this->query['groupBy'] = ' GROUP BY ';
        foreach ($this->parts['groupBys'] as $group_by) {
            $result = $this->process_group_by_entry($group_by);
            if ($result) {
                continue;
            }
        }
        if (substr($this->query['groupBy'], strlen($this->query['groupBy']) - 2) == ', ') {
            $this->query['groupBy'] = substr($this->query['groupBy'], 0, strlen($this->query['groupBy']) - 2) . ' ';
        }
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSGROUPBYS_END');
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_group_by_entry(string $group_by): bool
    {
        $this->query['groupBy'] .= $group_by . ', ';
        return false;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_order_by_entry(array $order_by): bool
    {
        if ($order_by['type'] == 'mysql') {
            $this->query['orderBy'] .= ' ' . $order_by['field'] . ', ';
            return true;
        }
        if (isset($order_by['table'])) {
            $this->query['orderBy'] .= $order_by['table'] . '.';
        }
        $this->query['orderBy'] .= $order_by['field'] . ', ';
        return false;
    }
    /**
     * process select list entries
     * @since ZC v1.5.7
     */
    protected function process_select_list()
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSSELECTLIST_START');
        if (count($this->parts['selectList']) == 0) {
            return;
        }
        foreach ($this->parts['selectList'] as $select_list) {
            if (trim((string) $this->query['select']) != 'SELECT') {
                $this->query['select'] .= ', ';
            }
            $this->query['select'] .= $select_list;
        }
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSSELECTLIST_END');
    }
    /**
     * process bindVars clauses
     * @since ZC v1.5.7
     */
    protected function process_bind_vars()
    {
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSBINDVARS_START');
        if (count($this->parts['bindVars']) == 0) {
            return;
        }
        foreach ($this->parts['bindVars'] as $bind_vars) {
            $this->query['mainSql'] = $this->db_conn->bind_vars($this->query['mainSql'], $bind_vars[0], $bind_vars[1], $bind_vars[2]);
            if (isset($this->query['countSql'])) {
                $this->query['countSql'] = $this->db_conn->bind_vars($this->query['countSql'], $bind_vars[0], $bind_vars[1], $bind_vars[2]);
            }
        }
        $this->notify('NOTIFY_QUERYBUILDER_PROCESSBINDVARS_END');
    }
    /**
     * getter
     *
     * @return mixed
     * @since ZC v1.5.7
     */
    public function get_parts()
    {
        return $this->parts;
    }
    /**
     * getter
     *
     * @return mixed
     * @since ZC v1.5.7
     */
    public function get_query()
    {
        return $this->query;
    }
    /**
     * setter
     *
     * @param $value
     * @since ZC v1.5.7
     */
    public function set_parts($value): void
    {
        $this->parts = $value;
        $this->notify('NOTIFY_QUERYBUILDER_SETPARTS_START');
    }
}