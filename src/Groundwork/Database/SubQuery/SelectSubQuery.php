<?php

namespace Groundwork\Database\SubQuery;

use Groundwork\Database\Query;

class SelectSubQuery extends SubQuery
{
    /**
     * @var string The column to name the sub-query to.
     */
    protected string $column;

    public function __construct(string $column, Query $query)
    {
        $this->column = $column;

        parent::__construct(null, $query);
    }

    /**
     * Gets or sets the column name
     *
     * @param string|null $key
     *
     * @return string|null
     */
    public function key(string $key = null) : ?string
    {
        if (is_null($key)) {
            return $key;
        }

        $this->column = $key;
        return null;
    }

    public function get(int $index = 0) : string
    {
        $sql = '(' . $this->query->generateQuery()[0] . ')';

        return $sql . ' AS ' . $this->column;
    }
}