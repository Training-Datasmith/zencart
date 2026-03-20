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
class Filter_Manager
{
    protected $filters = [];
    public function __construct(protected array $filter_definitions, protected \Zencart\Filters\Filter_Factory $filter_factory)
    {
    }
    /**
     * @since ZC v1.5.8
     */
    public function build(): void
    {
        $this->filters = [];
        if (!$this->has_filters()) {
            return;
        }
        foreach ($this->filter_definitions as $filter_definition) {
            $filter = $this->filter_factory->make($filter_definition);
            $this->filters[] = $filter;
            $filter->make($filter_definition);
        }
    }
    /**
     * @since ZC v1.5.8
     */
    public function process_request(Request $request, $query)
    {
        if (!$this->has_filters()) {
            return $query;
        }
        foreach ($this->filters as $filter) {
            $query = $filter->process_request($request, $query);
        }
        return $query;
    }
    /**
     * @since ZC v1.5.8
     */
    public function has_filters(): bool
    {
        if (!count($this->filter_definitions)) {
            return false;
        }
        return true;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_filters(): array
    {
        return $this->filters;
    }
}