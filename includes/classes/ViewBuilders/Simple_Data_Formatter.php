<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Oct 25 Modified in v2.2.0 $
 */
namespace Zencart\View_Builders;

/**
 * @since ZC v1.5.8
 */
class Simple_Data_Formatter
{
    public function __construct(protected \Zencart\Request\Request $request, protected \Zencart\View_Builders\Table_View_Definition $table_definition, protected \Zencart\View_Builders\Native_Paginator $result_set, protected $derived_items)
    {
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_table_headers(): array
    {
        $col_headers = [];
        $columns = $this->table_definition->get_parameter('columns');
        foreach ($columns as $column) {
            $header_class = $this->get_col_header_main_class($column);
            $col_headers[] = ['headerClass' => $header_class, 'title' => $column['title']];
        }
        return $col_headers;
    }
    /**
     * @since ZC v1.5.8
     * @return array{value: mixed, class: mixed, original: mixed}[][]
     */
    public function get_table_data(): array
    {
        $table_data = [];
        $columns = $this->table_definition->get_parameter('columns');
        $fields = array_keys($columns);
        $column_data = [];
        foreach ($this->result_set as $result) {
            foreach ($fields as $field) {
                $value = $this->derived_items->process($result, $field, $columns[$field]);
                $original_value = $this->get_row_field($result, $field);
                $class = '';
                // if column class is set as a closure, call it and pass in the value from $result->field; else assume it is a string
                $class_def = $columns[$field]['class'] ?? null;
                if ($class_def instanceof \Closure || is_callable($class_def)) {
                    $class = $class_def($original_value);
                } elseif (is_string($class_def)) {
                    $class = $class_def;
                }
                $column_data[$field] = ['value' => $value, 'class' => $class, 'original' => $original_value];
            }
            $table_data[] = $column_data;
        }
        return $table_data;
    }
    /**
     * @since ZC v1.5.8
     */
    public function is_row_selected(array $table_row): bool
    {
        $col_key_from_request = $this->request->input($this->table_definition->col_key_name());
        $col_key_field = $this->table_definition->get_parameter('colKey');
        $current_row = $this->current_row_from_request();
        if (is_null($col_key_from_request) && $current_row->{$col_key_field} == $table_row[$col_key_field]['value']) {
            return true;
        }
        if ($col_key_from_request == $table_row[$col_key_field]['value']) {
            return true;
        }
        return false;
    }
    /**
     * @since ZC v1.5.8
     */
    public function current_row_from_request()
    {
        $col_key_from_request = $this->request->input($this->table_definition->col_key_name());
        $col_key_field = $this->table_definition->get_parameter('colKey');
        if (!is_null($col_key_from_request)) {
            $result = null;
            foreach ($this->result_set->get_collection() as $row) {
                if ((string) $row[$col_key_field] === (string) $col_key_from_request) {
                    $result = $row;
                    break;
                }
            }
        } else {
            $result = $this->result_set->get_collection()[0] ?? null;
        }
        return $result;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_selected_row_link(array $table_row): string
    {
        $pager_var = $this->table_definition->get_parameter('pagerVariable');
        $params = $pager_var . '=' . $this->request->input($pager_var, 1);
        $params .= '&' . $this->table_definition->col_key_name() . '=' . $table_row[$this->table_definition->get_parameter('colKey')]['value'];
        return zen_href_link($this->request->input('cmd'), $params);
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_not_selected_row_link(array $table_row): string
    {
        $pager_var = $this->table_definition->get_parameter('pagerVariable');
        $params = $pager_var . '=' . $this->request->input($pager_var, 1);
        $params .= '&' . $this->table_definition->col_key_name() . '=' . $table_row[$this->table_definition->get_parameter('colKey')]['value'];
        return zen_href_link($this->request->input('cmd'), $params);
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_result_set(): \Zencart\View_Builders\Native_Paginator
    {
        return $this->result_set;
    }
    /**
     * @since ZC v2.2.0
     */
    protected function get_row_field($row, string $field, $default = null)
    {
        if (is_array($row)) {
            return $row[$field] ?? $default;
        }
        if ($row instanceof \ArrayAccess && isset($row[$field])) {
            return $row[$field];
        }
        if (is_object($row) && isset($row->{$field})) {
            return $row->{$field};
        }
        return $default;
    }
    /**
     * @since ZC v1.5.8
     */
    public function has_row_actions(): bool
    {
        return $this->table_definition->has_row_actions();
    }
    /**
     * @since ZC v1.5.8
     * @return mixed[]
     */
    public function get_row_actions($table_row): array
    {
        $row_actions = $this->table_definition->get_row_actions();
        $processed = [];
        foreach ($row_actions as $row_action) {
            $processed[] = $this->process_row_action($row_action, $table_row);
        }
        return $processed;
    }
    /**
     * @since ZC v1.5.8
     */
    public function has_button_actions()
    {
        $button_actions = $this->get_raw_button_actions();
        if (count($button_actions) == 0) {
            return false;
        }
        return count($button_actions) > 0;
    }
    /**
     * @since ZC v1.5.8
     * @return mixed[]
     */
    public function get_button_actions(): array
    {
        $button_actions = $this->get_raw_button_actions();
        $processed = [];
        foreach ($button_actions as $button_action) {
            $button_action['hrefLink'] = $this->process_button_action_link($button_action);
            $processed[] = $button_action;
        }
        return $processed;
    }
    /**
     * @since ZC v1.5.8
     * @return mixed[]
     */
    protected function get_raw_button_actions(): array
    {
        $button_actions = $this->table_definition->get_button_actions();
        if (count($button_actions) == 0) {
            return [];
        }
        $processed = [];
        foreach ($button_actions as $button_action) {
            if ($this->button_passes_white_list($button_action) && $this->button_passes_black_list($button_action)) {
                $processed[] = $button_action;
            }
        }
        return $processed;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_button_action_link(array $button_action): string
    {
        return 'action=' . $button_action['action'];
    }
    /**
     * @since ZC v1.5.8
     */
    protected function button_passes_white_list(array $button_action): bool
    {
        $action = $this->request->input('action');
        if (!isset($button_action['whitelist'])) {
            return true;
        }
        if (in_array($action, $button_action['whitelist'])) {
            return true;
        }
        return false;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function button_passes_black_list(array $button_action): bool
    {
        $action = $this->request->input('action');
        if (!isset($button_action['blacklist'])) {
            return true;
        }
        if (in_array($action, $button_action['blacklist'])) {
            return false;
        }
        return true;
    }
    /**
     * @since ZC v1.5.8
     * @return mixed[]
     */
    protected function process_row_action(array $row_action, $table_row): array
    {
        $processed = $row_action;
        $link = $this->build_row_action_link($row_action, $table_row);
        $processed['hrefLink'] = $link;
        return $processed;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function build_row_action_link(array $row_action, array $table_row): string
    {
        $pager_var = $this->table_definition->get_parameter('pagerVariable');
        $link = $pager_var . '=' . $this->request->input($pager_var, 1);
        $link .= '&action=' . $row_action['action'];
        $table_row_link = $this->process_row_action_table_row_link($row_action, $table_row);
        $table_row_link = rtrim($table_row_link, '&');
        return $link . ('&' . $table_row_link);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_row_action_table_row_link(array $row_action, array $table_row): string
    {
        $link = '';
        if (!isset($row_action['linkParams'])) {
            return $link;
        }
        foreach ($row_action['linkParams'] as $link_params) {
            if ($link_params['source'] !== 'tableRow') {
                continue;
            }
            $link .= $link_params['param'] . '=' . $table_row[$link_params['field']]['original'] . '&';
        }
        return $link;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function get_col_header_main_class($col_def): string
    {
        return 'dataTableHeadingContent';
    }
}