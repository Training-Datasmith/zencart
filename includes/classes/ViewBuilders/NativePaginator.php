<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
namespace Zencart\View_Builders;

use ArrayObject;
use IteratorAggregate;
use Traversable;
/**
 * @since ZC v2.2.0
 */
class Native_Paginator implements IteratorAggregate
{
    /** @var ArrayObject<int, ArrayObject> */
    protected ArrayObject $collection;
    public function __construct(array $items, protected int $total, protected int $per_page, protected int $current_page, protected string $page_name = 'page')
    {
        $wrapped = [];
        foreach ($items as $item) {
            $wrapped[] = new ArrayObject((array) $item, ArrayObject::ARRAY_AS_PROPS);
        }
        $this->collection = new ArrayObject($wrapped);
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_page_name(): string
    {
        return $this->page_name;
    }
    /**
     * @since ZC v2.2.0
     */
    public function current_page(): int
    {
        return $this->current_page;
    }
    /**
     * @since ZC v2.2.0
     */
    public function per_page(): int
    {
        return $this->per_page;
    }
    /**
     * @since ZC v2.2.0
     */
    public function total(): int
    {
        return $this->total;
    }
    /**
     * @since ZC v2.2.0
     */
    public function first_item(): int
    {
        if ($this->total === 0) {
            return 0;
        }
        return ($this->current_page - 1) * $this->per_page + 1;
    }
    /**
     * @since ZC v2.2.0
     */
    public function last_item(): int
    {
        if ($this->total === 0) {
            return 0;
        }
        return min($this->current_page * $this->per_page, $this->total);
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_collection(): ArrayObject
    {
        return $this->collection;
    }
    /**
     * @since ZC v2.2.0
     */
    public function getIterator(): Traversable
    {
        return $this->collection->getIterator();
    }
}