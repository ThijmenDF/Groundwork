<?php

namespace Groundwork\Database;

use Groundwork\Database\SubQuery\SelectSubQuery;
use Groundwork\Database\WhereStatements\WhereStatement;
use Groundwork\Utils\Table;

trait CommonStatements {

    /** @var Table The ORDER BY parts of the query */
    protected Table $order;

    /** @var Table The GROUP BY parts of the query */
    protected Table $group;

    /** @var WhereStatement|null  */
    protected ?WhereStatement $having = null;

    /** @var int|null The LIMIT part of the query */
    protected ?int $limit = null;

    /** @var int The OFFSET (part of LIMIT) for the SELECT actions */
    protected int $offset = 0;

    /**
     * Adds a sub-query selector as a new temporary column.
     *
     * @param Query  $query
     * @param string $column
     *
     * @return static
     */
    public function addSelect(Query $query, string $column) : self
    {
        $this->data->push(new SelectSubQuery($column, $query));

        return $this;
    }

    /**
     * Sets a limit as to how many results may be returned, or how many rows may be updated.
     *
     * @param int $limit
     *
     * @return static
     */
    public function limit(int $limit = 1) : self
    {
        if ($limit === -1) {
            $this->limit = null;
        } else {
            $this->limit = $limit;
        }


        return $this;
    }

    /**
     * Sets the offset for SELECT queries.
     *
     * @param int $offset
     *
     * @return static
     */
    public function offset(int $offset = 0) : self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Adds an ORDER BY query
     *
     * @param string $column
     * @param bool   $reversed
     *
     * @return static
     */
    public function order(string $column, bool $reversed = false) : self
    {
        $this->order->push("`$column` " . ($reversed ? 'DESC' : 'ASC'));

        return $this;
    }

    /**
     * Adds an ORDER BY query as reversed.
     *
     * @param string $column
     *
     * @return static
     */
    public function orderDesc(string $column) : self
    {
        return $this->order($column, true);
    }

    /**
     * Sorts by the created at date, or your own column, descending
     *
     * @param string $column
     *
     * @return static
     */
    public function latest(string $column = 'created_at') : self
    {
        return $this->order($column);
    }

    /**
     * Sorts by the created at date, or your own column name, ascending
     *
     * @param string $column
     *
     * @return static
     */
    public function oldest(string $column = 'created_at') : self
    {
        return $this->order($column, true);
    }

    /**
     * Removes the previously assigned ordering
     *
     * @return static
     */
    public function reorder() : self
    {
        $this->order = table();

        return $this;
    }

    /**
     * Adds a GROUP BY query
     *
     * @param mixed ...$columns
     *
     * @return static
     */
    public function group(...$columns) : self
    {
        $this->group->merge(table($columns));

        return $this;
    }

    /**
     * Adds the HAVING statement. Works in conjunction with group and
     *
     * @param string $key     The column
     * @param mixed  $compare The compare method. Can be use in place of `$value`, in which case it's set to '='
     * @param mixed  $value   The value, if null will be replaced by `$compare`
     *
     * @return static
     */
    public function having(string $key, $compare, $value = null) : self
    {
        if (is_null($value)) {
            $value = $compare;
            $compare = '=';
        }

        $this->having = new WhereStatement("$key $compare ?", [$value]);

        return $this;
    }

    /**
     * Removes the 'deleted at' filter that's typically applied on queries.
     * 
     * @return static
     */
    public function withDeleted() : self
    {
        $this->includeDeleted = true;

        return $this;
    }

    /**
     * Only fetch deleted models.
     * 
     * @return static
     */
    public function onlyDeleted() : self
    {
        $this->withDeleted();

        $this->onlyDeleted = true;

        return $this;
    }

}