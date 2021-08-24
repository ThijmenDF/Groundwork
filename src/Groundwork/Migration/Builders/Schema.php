<?php

namespace Groundwork\Migration\Builders;

use Groundwork\Database\Db;
use Groundwork\Exceptions\Database\DatabaseException;
use Groundwork\Utils\Table;

/**
 * Allows the creation or alteration of an SQL Table
 */
class Schema implements Builder {

    public string $table;

    public string $action = 'create';

    public Blueprint $blueprint;

    /**
     * @param string        $table   The table name
     * @param callable|null $builder A callable method that fills in the Blueprint
     * @param string        $action  The action to perform (e.g. 'create' or 'alter')
     *
     * @throws DatabaseException
     */
    public function __construct(string $table, callable $builder = null, string $action = 'create')
    {
        $this->table = $table;

        $this->action = $action;

        $this->blueprint = new Blueprint($table);

        if ($builder) {
            $builder($this->blueprint);
        }

        $this->execute();
    }

    /**
     * Creates a new table.
     *
     * @param string   $table
     * @param callable $builder
     *
     * @return static
     * @throws DatabaseException
     */
    public static function create(string $table, callable $builder) : Schema
    {
        return new static($table, $builder, 'create');
    }

    /**
     * Alters an existing table.
     *
     * @param string   $table
     * @param callable $builder
     *
     * @return static
     * @throws DatabaseException
     */
    public static function alter(string $table, callable $builder) : Schema
    {
        return new static($table, $builder, 'alter');
    }

    /**
     * Drops a table
     *
     * @param string $table
     *
     * @return static
     * @throws DatabaseException
     */
    public static function drop(string $table) : Schema
    {
        return new static($table, null, 'drop');
    }

    /**
     * Drops a table if it exists
     *
     * @param string $table
     *
     * @return static
     * @throws DatabaseException
     */
    public static function dropIfExists(string $table) : Schema
    {
        return new static($table, null, 'dropIfExists');
    }

    /**
     * Clears the table content and resets the auto-increment counter.
     *
     * @param string $table
     *
     * @return Schema
     * @throws DatabaseException
     */
    public static function truncate(string $table) : Schema
    {
        return new static($table, null, 'truncate');
    }

    /**
     * Builds a list of queries to execute.
     *
     * @return Table
     */
    public function build() : Table
    {
        return $this->blueprint->build($this->action);
    }

    /**
     * Executes the schema.
     *
     * @throws DatabaseException
     * @return bool
     */
    public function execute() : bool
    {
        $queries = $this->build();
        $db = Db::getInstance();

        return $queries->every(function(string $query) use($db) {
            $result = $db->raw($query);

            if (!$result) {
                throw new DatabaseException("Schema failed to run! " . $db->error() . " - SQL: $query");
            }

            return !!$result;
        });
    }
}