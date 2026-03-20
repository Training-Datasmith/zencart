<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\View_Builders;

/**
 * @since ZC v1.5.8
 */
class Table_View_Definition
{
    public function __construct(
        /**
         * $definition is an array holding the table definition
         */
        protected array $definition = []
    )
    {
        $this->set_defaults();
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_definition(): array
    {
        return $this->definition;
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_parameter(string $field, $definition): Table_View_Definition
    {
        $this->definition[$field] = $definition;
        return $this;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_parameter(string $field)
    {
        return $this->definition[$field] ?? null;
    }
    /**
     * @since ZC v1.5.8
     */
    public function add_button_action($definition): Table_View_Definition
    {
        $this->definition['buttonActions'][] = $definition;
        return $this;
    }
    /**
     * @since ZC v1.5.8
     */
    public function add_row_action($definition): Table_View_Definition
    {
        $this->definition['rowActions'][] = $definition;
        return $this;
    }
    /**
     * @since ZC v1.5.8
     */
    public function add_column(string $field, $definition): Table_View_Definition
    {
        $this->definition['columns'][$field] = $definition;
        return $this;
    }
    /**
     * @since ZC v1.5.8
     */
    public function add_column_before($index, $new_key, $data): Table_View_Definition
    {
        $columns = $this->definition['columns'];
        $columns = $this->insert_before($columns, $index, $new_key, $data);
        $this->definition['columns'] = $columns;
        return $this;
    }
    /**
     * @since ZC v1.5.8
     */
    public function add_column_after($index, $new_key, $data): Table_View_Definition
    {
        $columns = $this->definition['columns'];
        $columns = $this->insert_after($columns, $index, $new_key, $data);
        $this->definition['columns'] = $columns;
        return $this;
    }
    /**
     * @since ZC v1.5.8
     * @return mixed[]
     */
    public function get_headers(): array
    {
        $headers = [];
        foreach ($this->definition['columns'] as $column) {
            $headers[] = $column['title'] ?? '';
        }
        return $headers;
    }
    /**
     * @since ZC v1.5.8
     */
    public function is_paginated(): bool
    {
        return $this->definition['paginated'];
    }
    /**
     * @since ZC v1.5.8
     */
    public function col_key_name(): string
    {
        return $this->definition['colKeyName'];
    }
    /**
     * @since ZC v1.5.8
     */
    public function has_row_actions(): bool
    {
        return count($this->definition['rowActions']) > 0;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_row_actions(): array
    {
        return $this->definition['rowActions'];
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_button_actions(): array
    {
        return $this->definition['buttonActions'];
    }
    /**
     * @since ZC v1.5.8
     */
    protected function set_defaults()
    {
        $this->definition['paginated'] ??= true;
        $this->definition['columns'] ??= [];
        $this->definition['buttonActions'] ??= [];
        $this->definition['rowActions'] ??= [];
        $this->definition['maxRowCount'] ??= 10;
        $this->definition['colKeyName'] ??= 'colKey';
        $this->definition['pagerVariable'] ??= 'page';
        $this->definition['colKey'] ??= 'id';
    }
    /**
     * @since ZC v1.5.8
     */
    protected function add_definitions($original, $addition): float|int|array
    {
        return $original + $addition;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function insert_before($input, $index, $new_key, $element)
    {
        if (!array_key_exists($index, $input)) {
            return $input;
        }
        $tmp_array = [];
        foreach ($input as $key => $value) {
            if ($key === $index) {
                $tmp_array[$new_key] = $element;
            }
            $tmp_array[$key] = $value;
        }
        return $tmp_array;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function insert_after($input, $index, $new_key, $element)
    {
        if (!array_key_exists($index, $input)) {
            return $input;
        }
        $tmp_array = [];
        foreach ($input as $key => $value) {
            $tmp_array[$key] = $value;
            if ($key === $index) {
                $tmp_array[$new_key] = $element;
            }
        }
        return $tmp_array;
    }
}