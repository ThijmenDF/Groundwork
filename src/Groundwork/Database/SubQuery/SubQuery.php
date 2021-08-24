<?php

namespace Groundwork\Database\SubQuery;

use Groundwork\Database\Query;
use Groundwork\Database\WhereStatements\WhereStatement;
use Groundwork\Utils\Table;

class SubQuery
{
    /**
     * The sub-query to run.
     *
     * @var Query
     */
    protected Query $query;

    /**
     * The where statement to parse the query into.
     *
     * @var WhereStatement|null
     */
    protected ?WhereStatement $statement;


    public function __construct(?WhereStatement $statement, Query $query)
    {
        $this->statement = $statement;
        $this->query = $query;
    }

    /**
     * Gets or sets the Query.
     *
     * @param Query|null $query
     *
     * @return Query|null
     */
    public function query(Query $query = null) : ?Query
    {
        if (is_null($query)) {
            return $this->query;
        }

        $this->query = $query;
        return null;
    }

    /**
     * Gets or sets the Where Statement.
     *
     * @param WhereStatement|null $statement
     *
     * @return WhereStatement|null
     */
    public function statement(WhereStatement $statement = null) : ?WhereStatement
    {
        if (is_null($statement)) {
            return $this->statement;
        }

        $this->statement = $statement;
        return null;
    }

    /**
     * Gets the query's SQL.
     *
     * @param int $index
     *
     * @return string
     */
    public function get(int $index = 0) : string
    {
        $sql = $this->statement->get($index);

        $sql .= '(' . $this->query->generateQuery()[0] . ')';

        return $sql;
    }

    /**
     * Gets the query's bindings.
     *
     * @return Table
     */
    public function bindings() : Table
    {
        return $this->query->generateQuery()[1];
    }
}