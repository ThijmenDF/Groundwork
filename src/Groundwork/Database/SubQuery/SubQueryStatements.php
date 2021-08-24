<?php

namespace Groundwork\Database\SubQuery;

use Groundwork\Database\Query;
use Groundwork\Database\WhereStatements\WhereStatement;
use InvalidArgumentException;

trait SubQueryStatements
{
    /**
     * @var array|string[] A list of operators that are allowed for single columns.
     */
    private array $allowedSingleOperators = [
        '=', '>', '>=', '<', '<=', '!=', '<>', '<=>',
        '= ALL', '<> ALL', '= ANY', '<> ANY', '= SOME',  '<> SOME',
        'IN', 'NOT IN',
    ];

    /**
     * @var array|string[] A list of operators that are allowed for multiple columns.
     */
    private array $allowedMultipleOperators = [
        '=', '>', '>=', '<', '<=', '!=', '<>', '<=>',
    ];

    /**
     * @var array|string[] A list of operators that are allowed for no columns.
     */
    private array $allowedNullOperators = [
        'EXISTS', 'NOT EXISTS'
    ];

    /**
     * Adds a sub-query to the WHERE statement.
     *
     * @param string|array|null $key      The column(s) to compare with
     * @param string            $operator Depending on which column(s) (or none) are selected, this can be one of
     *                                    $allowedSingleOperators, $allowedMultipleOperators or $allowedNullOperators.
     * @param Query             $query    The query to execute
     * @param string            $mode     The boolean mode. Can be 'AND' or 'OR'. Defaults to 'AND'.
     *
     * @return static
     */
    public function subQuery($key, string $operator, Query $query, string $mode = 'AND') : self
    {
        $sql = '';

        switch (gettype($key)) {
            case 'array':
                $this->validateOperator($operator, $this->allowedMultipleOperators);

                $sql .= '(`' . implode('`,`', $key) . '`)';
                break;
            case 'string':
                $this->validateOperator($operator, $this->allowedSingleOperators);

                $sql .= "`$key`";
                break;
            case 'NULL':
                $this->validateOperator($operator, $this->allowedNullOperators);
                break;
        }

        $statement = new WhereStatement(
            $sql . " $operator",
            null,
            $mode
        );

        $this->_addSubQuery($statement, $query);

        return $this;
    }

    /**
     * Adds a 'AND `$key` = ALL ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     * @param string $mode  Boolean mode ('AND' or 'OR').
     *
     * @return $this
     */
    public function subAll(string $key, Query $query, string $mode = 'AND') : self
    {
        return $this->subQuery($key, '= ALL', $query, $mode);
    }

    /**
     * Adds a 'OR `$key` = ALL ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     *
     * @return $this
     */
    public function orSubAll(string $key, Query $query) : self
    {
        return $this->subAll($key, $query, 'OR');
    }

    /**
     * Adds a 'AND `$key` <> ALL ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     * @param string $mode  Boolean mode ('AND' or 'OR').
     *
     * @return $this
     */
    public function subNotAll(string $key, Query $query, string $mode = 'AND') : self
    {
        return $this->subNotIn($key, $query, $mode);
    }

    /**
     * Adds a 'OR `$key` <> ALL ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     *
     * @return $this
     */
    public function orSubNotAll(string $key, Query $query) : self
    {
        return $this->subNotAll($key, $query, 'OR');
    }

    /**
     * Adds a 'AND `$key` = ANY ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     * @param string $mode  Boolean mode ('AND' or 'OR').
     *
     * @return $this
     */
    public function subAny(string $key, Query $query, string $mode = 'AND') : self
    {
        return $this->subIn($key, $query, $mode);
    }

    /**
     * Adds a 'OR `$key` = ANY ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     *
     * @return $this
     */
    public function orSubAny(string $key, Query $query) : self
    {
        return $this->subAny($key, $query, 'OR');
    }

    /**
     * Adds a 'AND `$key` <> ANY ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     * @param string $mode  Boolean mode ('AND' or 'OR').
     *
     * @return $this
     */
    public function subNotAny(string $key, Query $query, string $mode = 'AND') : self
    {
        return $this->subQuery($key, '<> ANY', $query, $mode);
    }

    /**
     * Adds a 'OR `$key` <> ANY ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     *
     * @return $this
     */
    public function orSubNotAny(string $key, Query $query) : self
    {
        return $this->subNotAny($key, $query, 'OR');
    }

    /**
     * Adds a 'AND `$key` IN ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     * @param string $mode  Boolean mode ('AND' or 'OR').
     *
     * @return $this
     */
    public function subIn(string $key, Query $query, string $mode = 'AND') : self
    {
        return $this->subQuery($key, 'IN', $query, $mode);
    }

    /**
     * Adds a 'OR `$key` IN ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     *
     * @return $this
     */
    public function orSubIn(string $key, Query $query) : self
    {
        return $this->subIn($key, $query, 'OR');
    }

    /**
     * Adds a 'AND `$key` NOT IN ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     * @param string $mode  Boolean mode ('AND' or 'OR').
     *
     * @return $this
     */
    public function subNotIn(string $key, Query $query, string $mode = 'AND') : self
    {
        return $this->subQuery($key, 'NOT IN', $query, $mode);
    }

    /**
     * Adds a 'OR `$key` NOT IN ($query)' sub-query.
     *
     * @param string $key   The column.
     * @param Query  $query The sub-query. Make sure this only returns 1 column!
     *
     * @return $this
     */
    public function orSubNotIn(string $key, Query $query) : self
    {
        return $this->subNotIn($key, $query, 'OR');
    }

    /**
     * Adds a 'AND EXISTS ($query)' sub-query.
     *
     * @param Query  $query The sub-query.
     * @param string $mode  Boolean mode ('AND' or 'OR').
     *
     * @return $this
     */
    public function subExists(Query $query, string $mode = 'AND') : self
    {
        return $this->subQuery(null, 'EXISTS', $query, $mode);
    }

    /**
     * Adds a 'OR EXISTS ($query)' sub-query.
     *
     * @param Query  $query The sub-query.
     *
     * @return $this
     */
    public function orSubExists(Query $query) : self
    {
        return $this->subExists($query, 'OR');
    }

    /**
     * Adds a 'AND NOT EXISTS ($query)' sub-query.
     *
     * @param Query  $query The sub-query.
     * @param string $mode  Boolean mode ('AND' or 'OR').
     *
     * @return $this
     */
    public function subNotExists(Query $query, string $mode = 'AND') : self
    {
        return $this->subQuery(null, 'NOT EXISTS', $query, $mode);
    }

    /**
     * Adds a 'OR NOT EXISTS ($query)' sub-query.
     *
     * @param Query  $query The sub-query.
     *
     * @return $this
     */
    public function orSubNotExists(Query $query) : self
    {
        return $this->subNotExists($query, 'OR');
    }


    /**
     * Adds a SubQuery to the 'where' stack.
     *
     * @param WhereStatement $statement The where statement
     * @param Query          $query     The sub-query
     */
    private function _addSubQuery(WhereStatement $statement, Query $query)
    {
        $this->where->push(new SubQuery($statement, $query));
    }

    /**
     * Checks if the given operator is in the given list of allowed operators. If not, it throws a
     * InvalidArgumentException.
     *
     * @param string $operator
     * @param array  $allowedOperators
     * @throws InvalidArgumentException if the operator cannot be found in the given list.
     */
    private function validateOperator(string $operator, array $allowedOperators)
    {
        if (array_search($operator, $allowedOperators) === false) {
            throw new InvalidArgumentException('Multiple Operator ' . $operator . ' is invalid.');
        }
    }
}