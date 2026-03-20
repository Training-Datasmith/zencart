<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Filters;

use Zencart\Request\Request;
/**
 * @since ZC v1.5.8
 */
class Select_Where_Filter extends Base_Filter implements Request_Filter
{
    private $default;
    protected $filter_definition = [];
    protected $options = [];
    protected $parameters = [];
    /**
     * @since ZC v1.5.8
     */
    public function make(array $filter_definition): void
    {
        $this->filter_definition = $filter_definition;
        $this->default = $filter_definition['default'] ?? '';
        $this->options = $this->get_options_for_select($filter_definition);
        $this->parameters = $this->set_parameters($filter_definition);
    }
    /**
     * @since ZC v1.5.8
     */
    public function output(): string
    {
        return $this->make_select($this->options, $this->default, $this->parameters);
    }
    /**
     * @since ZC v1.5.8
     */
    public function process_request(Request $request, $query)
    {
        $this->default = $request->input($this->filter_definition['selectName'], '*');
        if ((string) $this->default == '*') {
            return $query;
        }
        if (is_array($query)) {
            return array_values(array_filter($query, function (array $row): bool {
                $field = $this->filter_definition['field'];
                return (string) ($row[$field] ?? '') === (string) $this->default;
            }));
        }
        if (is_object($query) && method_exists($query, 'where')) {
            return $query->where($this->filter_definition['field'], $this->default);
        }
        return $query;
    }
    /**
     * @since ZC v1.5.8
     */
    private function get_options_for_select(array $filter_definition): array
    {
        return $filter_definition['options'];
    }
    /**
     * @since ZC v1.5.8
     */
    private function set_parameters(array $filter_definition): array
    {
        $parameters['label'] = $filter_definition['label'];
        $parameters['name'] = $filter_definition['selectName'];
        $parameters['class'] = $filter_definition['class'] ?? '';
        $parameters['id'] = $filter_definition['id'] ?? $parameters['name'];
        $parameters['auto'] = $filter_definition['auto'] ?? false;
        return $parameters;
    }
}