<?php

namespace Groundwork\Database\Pagination;

use ArrayAccess;
use Countable;
use Generator;
use Groundwork\Database\Table;
use Groundwork\Traits\HasAttributes;
use Groundwork\Traits\Serializable;
use IteratorAggregate;
use JsonSerializable;

class PaginatedResult implements JsonSerializable, IteratorAggregate, Countable, ArrayAccess
{
    use Serializable, HasAttributes;

    /**
     * @param int            $total
     * @param int            $perPage
     * @param int            $currentPage
     * @param int            $lastPage
     * @param Table          $data
     * @param callable|array $routeGenerator
     */
    public function __construct(int $total, int $perPage, int $currentPage, int $lastPage, Table $data, $routeGenerator)
    {
        $this->fill([
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'count' => count($data),
            'first_page_url' => $routeGenerator(1),
            'last_page_url' => $routeGenerator($lastPage),
            'next_page_url' => $currentPage < $lastPage ? $routeGenerator($currentPage+1) : null,
            'prev_page_url' => $currentPage > 1 ? $routeGenerator( $currentPage-1) : null,
            'data' => $data
        ]);
    }

    /**
     * Returns an ArrayIterator for the values, so that foreach loops can work.
     *
     * @interface IteratorAggregate
     *
     * @return Generator
     */
    public function getIterator() : Generator
    {
        yield from $this->attributes['data'];
    }

    /**
     * Returns the amount of items in the attributes list.
     *
     * @return int
     */
    public function count() : int
    {
        return $this->attributes['count'];
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->attributes['data'][$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->attributes['data'][$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $this->attributes['data'][$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes['data'][$offset]);
    }
}