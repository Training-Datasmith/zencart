<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\View_Builders;

use Zencart\Request\Request;
use Zencart\Traits\Notifier_Manager;
/**
 * @since ZC v1.5.8
 */
abstract class Data_Table_Data_Source
{
    use Notifier_Manager;
    public function __construct(protected \Zencart\View_Builders\Table_View_Definition $table_definition)
    {
        $this->notify('NOTIFY_DATASOURCE_CONSTRUCTOR_END');
    }
    /**
     * @since ZC v1.5.8
     */
    abstract protected function build_initial_query();
    /**
     * @since ZC v1.5.8
     */
    public function process_request(Request $request)
    {
        $query = $this->build_initial_query($request);
        $this->notify('NOTIFY_DATASOURCE_PROCESSREQUEST', [], $query);
        return $query;
    }
    /**
     * @since ZC v1.5.8
     */
    public function process_query($query): Native_Paginator
    {
        $max_rows = $this->table_definition->is_paginated() ? (int) $this->table_definition->get_parameter('maxRowCount') : 100000;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        if (is_array($query)) {
            $total = count($query);
            $offset = ($page - 1) * $max_rows;
            if ($offset < 0) {
                $offset = 0;
            }
            $slice = array_slice($query, $offset, $max_rows);
            return new Native_Paginator($slice, $total, $max_rows, $page, 'page');
        }
        if (is_object($query) && method_exists($query, 'paginate')) {
            /** @var NativePaginator $results */
            $results = $query->paginate($max_rows, '*', 'page', $page);
            return $results;
        }
        return new Native_Paginator([], 0, $max_rows, $page, 'page');
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_table_definition(): Table_View_Definition
    {
        return $this->table_definition;
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_table_definition(Table_View_Definition $table_definition): void
    {
        $this->table_definition = $table_definition;
    }
}