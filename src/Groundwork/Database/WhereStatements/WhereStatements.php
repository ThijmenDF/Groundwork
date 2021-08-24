<?php

namespace Groundwork\Database\WhereStatements;

use Groundwork\Database\SubQuery\SubQuery;
use Groundwork\Database\Table;
use Groundwork\Utils\Table as BaseTable;

trait WhereStatements {

    /**
     * The WHERE parts of the query
     *
     * Because 'AND' and 'OR' can be grouped, so will this search pattern
     *
     * Items in the table consists out of WhereStatement, WhereGroupChange and SubQuery classes. The order of these
     * determine how the groups are structured.
     *
     * @var BaseTable
     */
    protected BaseTable $where;

    /**
     * The current depth of the group (how many brackets in)
     *
     * @var int
     */
    protected int $groupDepth = 0;

    /**
     * Applies the WHERE part of the query and updates the bindings.
     *
     * @param string    &$query    The query that's being built.
     * @param BaseTable  $bindings The bindings that contain the unprocessed values
     */
    private function applyWhere(string &$query, BaseTable $bindings) : void
    {
        if ($this->model && $this->model->softDeletes()) {
            if (!$this->includeDeleted) {

                // Apply a deleted_at filter (it cannot be deleted)
                $this->whereNull('deleted_at');

            } elseif ($this->onlyDeleted) {
                // Apply a deleted_at requirement (it must be deleted)
                $this->whereNotNull('deleted_at');

            }
        }

        if ($this->where->isNotEmpty()) {
            $query .= 'WHERE ';

            $currentDepth = 0;
            $currentIndex = 0;

            foreach ($this->where as $where) {

                switch (true) {

                    case $where instanceof WhereGroupChange:
                        // Open new groups while needed
                        while ($where->depth > $currentDepth) {
                            $query .= ($currentIndex ? $where->mode . ' ' : '') . '( ';
                            $currentDepth++;
                            $currentIndex = 0;
                        }

                        // Close groups while needed
                        while ($where->depth < $currentDepth) {
                            $query .= ') ';
                            $currentDepth--;
                        }
                        break;

                    case $where instanceof WhereStatement:
                    case $where instanceof SubQuery:
                        // Get the query part (it includes the MODE if index is larger than 0)
                        $query .= $where->get($currentIndex);

                        // Add bindings
                        $bindings->merge($where->bindings());

                        // Increment the index counter.
                        $currentIndex++;
                        break;

                    default:
                        throw new \InvalidArgumentException('A class was found within the \'where\' statements that cannot be processed! Type is ' . get_class($where));
                }

            }

            // Close groups until we reach 0 again
            while (0 < $currentDepth) {
                $query .= ')';
                $currentDepth--;
            }
        }
    }

    /**
     * Adds a where statement to the stack. Sets the group depth too.
     *
     * @param WhereStatement $statement
     */
    private function addWhere(WhereStatement $statement) {
        $this->where->push($statement);
    }

    /**
     * Step into a new group
     */
    private function startGroup(string $mode = 'AND') {
        $this->groupDepth++;
        $this->where->push(new WhereGroupChange($this->groupDepth, $mode));
    }

    /**
     * Leave a group
     */
    private function closeGroup() {
        $this->groupDepth--;
        $this->where->push(new WhereGroupChange($this->groupDepth));
    }

    /**
     * Internal method to add a new 'where' statement.
     *
     * @param mixed  $key     The column key.
     * @param mixed  $compare The compare method.
     * @param mixed  $value   The value(s). It may be an array (or a Table)
     * @param string $mode    The mode ('AND' or 'OR')
     *
     * @return WhereStatement
     */
    private function insertWhere($key, $compare, $value = null, string $mode = 'ADD') : WhereStatement
    {
        // Replace value if value is empty
        if (is_null($value)) {
            $value = $compare;
            $compare = '=';
        }

        // convert to an array
        $value = table($value)->values()->all();

        // Add a new 'where' filter to the stack.
        return new WhereStatement(
            "`$key` $compare" . (is_null($value) ? '' : ' ?'),
            $value,
            $mode
        );
    }

    /**
     * Sets a basic 'WHERE' query. All 'WHERES' are combined with an AND statement
     *
     * @param string|iterable|callable $key      The column name, an array with where statements or a closure that can be called.
     * @param mixed                    $operator The compare method (e.g. '=' or 'LIKE'). May be replaced with `$value`, as it defaults to '='.
     * @param mixed                    $value    The value to compare to. May be null, in which case the `$compare` is used as value.
     * @param string                   $mode     The boolean compare mode with the previous statement.
     *
     * @return static
     */
    public function where($key, $operator = null, $value = null, string $mode = 'AND') : self
    {
        // If it's an array
        if (is_iterable($key)) {
            $this->startGroup($mode);
            foreach ($key as $where) {
                $this->addWhere(
                    $this->insertWhere($where[0], $where[1], $where[2] ?? null, $mode)
                );
            }
            $this->closeGroup();
        }
        // check if it's callable
        elseif (is_callable($key)) {
            $this->startGroup($mode);
            $key($this);
            $this->closeGroup();

        }
        // otherwise, treat it as a string
        else {
            $this->where->push($this->insertWhere($key, $operator, $value, $mode));
        }

        return $this;
    }

    /**
     * Sets a basic 'WHERE' query. All 'WHERES' are combined with an OR statement
     *
     * @param string|iterable|callable $key      The column name, an array with where statements or a closure that can be called.
     * @param mixed                    $operator The compare method (e.g. '=' or 'LIKE'). May be replaced with `$value`, as it defaults to '='.
     * @param mixed                    $value    The value to compare to. May be null, in which case the `$compare` is used as value.
     *
     * @return static
     */
    public function orWhere($key, $operator = null, $value = null) : self
    {
        return $this->where($key, $operator, $value, 'OR');
    }

    /**
     * Matches `AND $key IS NULL`
     *
     * @param string $key
     *
     * @return static
     */
    public function whereNull(string $key) : self
    {
        $this->addWhere(new WhereStatement("`$key` IS NULL"));

        return $this;
    }

    /**
     * Matches `AND $key IS NOT NULL`
     *
     * @param string $key
     *
     * @return static
     */
    public function whereNotNull(string $key) : self
    {
        $this->addWhere(new WhereStatement("`$key` IS NOT NULL"));

        return $this;
    }

    /**
     * Matches `OR $key IS NULL`
     *
     * @param string $key
     *
     * @return static
     */
    public function orWhereNull(string $key) : self
    {
        $this->addWhere(new WhereStatement("`$key` IS NULL",null,'OR'));

        return $this;
    }

    /**
     * Matches `OR $key IS NOT NULL`
     *
     * @param string $key
     *
     * @return static
     */
    public function orWhereNotNull(string $key) : self
    {
        $this->addWhere(new WhereStatement("`$key` IS NOT NULL",null,'OR'));

        return $this;
    }

    /**
     * Matches `AND $key IN [$values]`
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function whereIn(string $key, array $values) : self
    {
        if (empty($values)) { // WHERE `key` IN () statement is incorrect syntax. Replace with WHERE FALSE
            $this->addWhere(new WhereStatement("FALSE"));
        } else {
            $this->addWhere(new WhereStatement(
                "`$key` IN (" . Table::repeat(count($values), '?')->implode(',') . ")",
                $values
            ));
        }

        return $this;
    }

    /**
     * Matches `AND $key NOT IN [$values]`
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function whereNotIn(string $key, array $values) : self
    {
        if (empty($values)) {// WHERE `key` NOT IN () statement is incorrect syntax. Replace with WHERE TRUE
            $this->addWhere(new WhereStatement("TRUE"));
        } else {
            $this->addWhere(new WhereStatement(
                "`$key` NOT IN (" . Table::repeat(count($values), '?')->implode(',') . ")",
                $values
            ));
        }
        return $this;
    }


    /**
     * Matches `OR $key IN [$values]`
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function orWhereIn(string $key, array $values) : self
    {
        if (empty($values)) {// WHERE `key` IN () statement is incorrect syntax. Replace with WHERE FALSE
            $this->addWhere(new WhereStatement("FALSE", null, 'OR'));
        } else {
            $this->addWhere(new WhereStatement(
                "`$key` IN (" . Table::repeat(count($values), '?')->implode(',') . ")",
                $values,
                'OR'
            ));
        }

        return $this;
    }

    /**
     * Matches `OR $key NOT IN [$values]`
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function orWhereNotIn(string $key, array $values) : self
    {
        if (empty($values)) {// WHERE `key`NOT IN () statement is incorrect syntax. Replace with WHERE TRUE
            $this->addWhere(new WhereStatement("TRUE", null, 'OR'));
        } else {
            $this->addWhere(new WhereStatement(
                "`$key` NOT IN (" . Table::repeat(count($values), '?')->implode(',') . ")",
                $values,
                'OR'
            ));
        }

        return $this;
    }

    /**
     * Matches `AND $key BETWEEN [value1] AND [value2]`
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function whereBetween(string $key, array $values) : self
    {
        $this->addWhere(new WhereStatement(
            "`$key` BETWEEN ? AND ?",
            $values
        ));

        return $this;
    }

    /**
     * Matches `OR $key BETWEEN [value1] AND [value2]`
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function orWhereBetween(string $key, array $values) : self
    {
        $this->addWhere(new WhereStatement(
            "`$key` BETWEEN ? AND ?",
            $values,
            'OR'
        ));

        return $this;
    }

    /**
     * Matches `AND $key NOT BETWEEN [value1] AND [value2]`
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function whereNotBetween(string $key, array $values) : self
    {
        $this->addWhere(new WhereStatement(
            "`$key` NOT BETWEEN ? AND ?",
            $values
        ));

        return $this;
    }

    /**
     * Matches `OR $key NOT BETWEEN [value1] AND [value2]`
     *
     * @param string $key
     * @param array  $values
     *
     * @return static
     */
    public function orWhereNotBetween(string $key, array $values) : self
    {
        $this->addWhere(new WhereStatement(
            "`$key` NOT BETWEEN ? AND ?",
            $values,
            'OR'
        ));

        return $this;
    }

    /**
     * Matches `AND $column1 $compare $column2` (Column compare).
     *
     * @param string      $column1
     * @param string      $compare
     * @param string|null $column2 May be omitted, in which case it's replaced by `$compare` and `$compare` is set to '='.
     *
     * @return static
     */
    public function whereColumn(string $column1, string $compare, string $column2 = null) : self
    {
        if (is_null($column2)) {
            $column2 = $compare;
            $compare = '=';
        }

        $this->addWhere(new WhereStatement("`$column1` $compare `$column2`"));

        return $this;
    }

    /**
     * Matches `OR $column1 $compare $column2` (Column compare).
     *
     * @param string      $column1
     * @param string      $compare
     * @param string|null $column2 May be omitted, in which case it's replaced by `$compare` and `$compare` is set to '='.
     *
     * @return static
     */
    public function orWhereColumn(string $column1, string $compare, string $column2 = null) : self
    {
        if (is_null($column2)) {
            $column2 = $compare;
            $compare = '=';
        }

        $this->addWhere(new WhereStatement("`$column1` $compare `$column2`", null, 'OR'));

        return $this;
    }

    /**
     * Adds a where statement which is open for manual syntax. This does pose a potential security issue.
     * ##So... use with caution.
     *
     * @param string $query        The raw SQL statement to use in this WHERE section. Replace bindable variables with
     *                             a `?`.
     * @param array|null $bindings An [optional] array with variables. any `?` in the query will be replaced with these
     *                             in the same order.
     * @param bool       $or       Whether to append this query as a 'OR' compare instead of a 'AND' compare.
     *
     * @return static
     */
    public function whereRaw(string $query, array $bindings = null, bool $or = false) : self
    {
        $this->addWhere(new WhereStatement($query, $bindings, $or ? 'OR' : 'AND'));

        return $this;
    }

}