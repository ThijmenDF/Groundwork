<?php

namespace Groundwork\Database\WhereStatements;

/**
 * Class WhereStatement
 * @package Groundwork\Database
 */
class WhereStatement
{
    protected string $mode = 'AND';

    protected string $query = '';

    protected array $bindings = [];

    /**
     * WhereStatement constructor.
     *
     * @param string     $query    The SQL query (including ? matching the $data length)
     * @param array|null $bindings An array with data (to replace ? in the query with)
     * @param string     $mode     The boolean mode ('AND' or 'OR')
     */
    public function __construct(string $query, array $bindings = null, string $mode = 'AND')
    {
        $this->query = $query;
        $this->bindings = $bindings ?? [];
        $this->mode = $mode;
    }

    /**
     * Gets the query part. If `$index` is larger than 0 it will add the mode.
     *
     * @param int $index
     *
     * @return string
     */
    public function get(int $index = 0) : string
    {
        return ($index ? "$this->mode " : '') . "$this->query ";
    }

    /**
     * Sets or gets the query.
     *
     * @param string|null $query
     *
     * @return string|null
     */
    public function query(string $query = null) : ?string
    {
        if (is_null($query)) {
            return $this->query;
        }

        $this->query = $query;
        return null;
    }

    /**
     * Sets or gets the data.
     *
     * @param array|null $bindings
     *
     * @return array
     */
    public function bindings(array $bindings = null) : array
    {
        if (is_null($bindings)) {
            return $this->bindings;
        }
        return $this->bindings = $bindings;
    }

    /**
     * Sets or gets the mode.
     *
     * @param string|null $mode
     *
     * @return string
     */
    public function mode(string $mode = null) : string
    {
        if (is_null($mode)) {
            return $this->mode;
        }
        return $this->mode = $mode;
    }
}