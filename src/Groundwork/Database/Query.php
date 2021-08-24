<?php

namespace Groundwork\Database;

use Carbon\Carbon;
use Exception;
use Groundwork\Database\Pagination\Pagination;
use Groundwork\Database\SubQuery\SelectSubQuery;
use Groundwork\Exceptions\Database\EmptyResultException;
use Groundwork\Exceptions\Database\QueryException;
use Groundwork\Database\Model;
use Groundwork\Database\WhereStatements\WhereStatements;
use Groundwork\Database\SubQuery\SubQueryStatements;
use mysqli_result;
use Groundwork\Utils\Table as BaseTable;

class Query {

    use WhereStatements, CommonStatements, SubQueryStatements;

    /** The table name */
    protected string $table;

    /** The model class name (optional) */
    protected ?Model $model;

    /** The action to be performed */
    protected string $action = 'SELECT';

    /** The data for SELECT, UPDATE or INSERT actions */
    protected BaseTable $data;

    /** Whether to also include the soft-deleted items */
    protected bool $includeDeleted = false;

    /** Whether to only use soft-deleted items */
    protected bool $onlyDeleted = false;

    /**
     * Sets up a new query.
     *
     * @param string|Model $table The table name or model
     * @param Model|null   $model The model to cast results into. Optional. Only useful if the model's table is
     *                            different from the table the data is loaded from.
     */
    public function __construct($table, Model $model = null)
    {
        if ($table instanceof Model) {
            $this->table = $table->getTable();

            $this->model = $table;

        } else {
            $this->table = $table;

            $this->model = $model;
        }

        // Sets up the table attributes.
        $this->data = table();

        // WhereStatements
        $this->where = table();

        // CommonStatements
        $this->order = table();
        $this->group = table();
    }

    /**
     * Handles any BS from the clone method (we don't want things to keep referencing another).
     */
    public function __clone()
    {
        $this->data = clone $this->data;
        $this->where = clone $this->where;
        $this->order = clone $this->order;
        $this->group = clone $this->group;
    }

    /**
     * Sets up the query asn an SELECT query, and sets the fields to select.
     *
     * @param array|string $columns The columns to select. Can be a '*' for all columns (default)
     *
     * @return self
     */
    public function select($columns = ['*']) : self
    {
        $this->action = 'SELECT';

        // Overwrite the existing column selects
        $this->data = table($columns);

        return $this;
    }

    /**
     * Sets up the query as an INSERT INTO query and executes it.
     *
     * @param array $data
     *
     * @return bool|int
     */
    public function insert(array $data)
    {
        $this->action = 'INSERT';

        $this->data = table($data);

        try {
            return $this->execute();
        } catch (QueryException $exception) {
            return false;
        }
    }

    /**
     * Sets up the query as an UPDATE query and executes it.
     *
     * @param array $changes An associate array with all changes.
     *                       Requires at least one change to be effective.
     *
     * @return bool|int
     */
    public function update(array $changes)
    {
        $this->action = 'UPDATE';

        $this->data = table($changes);

        try {
            return $this->execute();
        } catch (QueryException $e) {
            return false;
        }
    }

    /**
     * Sets up the query as an DELETE FROM query and executes it.
     *
     * @param bool $force
     *
     * @return bool
     */
    public function delete(bool $force = false) : bool
    {
        if ($this->model && $this->model->softDeletes() && !$force) {
            // Cannot delete models that soft deletes. Update instead.
            return !!$this->update(['deleted_at' => Carbon::now()]);
        }
        
        $this->action = 'DELETE';

        try {
            return !!$this->execute();
        } catch (QueryException $e) {
            return false;
        }
    }

    /**
     * Creates a new Pagination object and returns it.
     *
     * @param int           $perPage
     * @param int|null      $firstPage
     * @param callable|null $urlHandler
     *
     * @return Pagination
     */
    public function paginate(int $perPage = 15, int $firstPage = null, callable $urlHandler = null) : Pagination
    {
        return new Pagination($this, $perPage, $firstPage, $urlHandler);
    }

    /**
     * Sets up the query to update a specific numeric column
     *
     * @param string $column
     * @param int    $amount [Optional] The amount to increment the value by
     *
     * @return self
     */
    public function increments(string $column, int $amount = 1) : self
    {
        // todo: add it. 

        return $this;
    }

    /**
     * Run the query and get all results
     *
     * @param array|string|null $columns
     *
     * @return Table|BaseTable
     * @throws QueryException
     */
    public function get($columns = null) : BaseTable
    {
        // If columns are passed, and they do not match the already configured columns, overwrite them.
        if ($columns) {
            $this->select($columns);
        }

        [$query, $bindings] = $this->generateQuery();

        // Run the query and return the result array immediately.
        $result = $this->runQuery($query, $bindings);

        return $this->getResults($result);
    }

    /**
     * Run the query and get the first result, or null
     *
     * @param array|string|null $columns
     *
     * @return null|Model|array
     */
    public function first($columns = null)
    {
        // If columns are passed, and they do not match the already configured columns, overwrite them.
        if ($columns) {
            $this->select($columns);
        }

        // Set limit to 1
        $this->limit();

        [$query, $bindings] = $this->generateQuery();

        try {
            $result = $this->runQuery($query, $bindings);
        } catch (Exception $ex) {
            return null;
        }

        $results = $this->getResults($result);

        // Return the first item or null
        return $results->first();
    }

    /**
     * Fetches the first result or throws an exception if none exist.
     *
     * @param array|string|null $columns
     *
     * @return array|Model
     * @throws Exception
     */
    public function firstOrFail($columns = null)
    {
        $result = $this->first($columns);

        if (is_null($result)) {
            throw new EmptyResultException('No results found.');
        }

        return $result;
    }

    /**
     * Attempts to count the result set.
     *
     * @return int
     */
    public function count() : int
    {
        $this->action = 'COUNT';

        // Run the query and return the result array immediately.
        $result = $this->runQuery(...$this->generateQuery());

        $result = $this->getResults($result);

        if ($result->isNotEmpty()) {
            return $result->first()['count'];
        }

        return 0;
    }

    /**
     * Attempts to 'run' the query, only telling if it was successful.
     *
     * @return bool|int
     * @throws QueryException
     */
    public function execute()
    {
        [$query, $bindings] = $this->generateQuery();

        $result = $this->runQuery($query, $bindings);

        // again, we're not going to give back a result. We only return IDs, counts or simply true / false.
        if ($result instanceof mysqli_result) {
            return true;
        }

        return $result;
    }


    /**
     * Attempts to stitch together the query and bindings.
     * 
     * @return array `[string $query, BaseTable $bindings]`
     */
    public function generateQuery() : array
    {
        $bindings = table();

        switch($this->action) {
            case 'UPDATE':

                $query = "UPDATE `$this->table` SET ";

                // Apply changed values
                $query .= $this->data
                    ->map(fn($value, $key) => "`$key` = ? ")
                    ->implode(', ');

                $bindings->merge($this->data->values());

                // Apply WHERE statement
                $this->applyWhere($query, $bindings);

                // Apply LIMIT statement
                $this->applyLimit($query);

                break;
            case 'INSERT':

                $query = "INSERT INTO `$this->table`(";
                
                // Apply all inserted value's keys
                $this->applyColumns($query, $this->data->keys());
                
                $query .= ') VALUES(';

                // adds as many ?'s as there are items in data and cut off the trailing comma.
                $query .= BaseTable::repeat(count($this->data), '?')
                    ->implode(', ');

                $query .= ')';

                $bindings->merge($this->data->values());

                break;
            case 'DELETE':

                $query = "DELETE FROM `$this->table` ";

                // Apply the WHERE statement
                $this->applyWhere($query, $bindings);

                // Apply the LIMIT statement
                $this->applyLimit($query);

                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'COUNT':

                $this->data->clear();
                $this->data->push('COUNT(*) as count');

            default:

                if ($this->data->isEmpty()) {
                    // default to 'all' if no specific columns have been selected
                    $this->data->push('*');
                }

                // Default is a SELECT statement
                $query = 'SELECT ';

                // apply SELECT columns
                $this->applyColumns($query, $this->data);

                $query .= "FROM `$this->table` ";

                // Apply WHERE statement
                $this->applyWhere($query, $bindings);

                // Apply ORDER BY statement
                $this->applyOrder($query);

                // Apply the GROUP BY statement
                $this->applyGroup($query, $bindings);

                // Apply LIMIT (and OFFSET) statement
                $this->applyLimit($query);
        }

        return [$query, $bindings];
    }

    /**
     * Parses the columns array and adds them to the query.
     * 
     * @param string    &$query The query that's being built.
     * @param BaseTable $data   The data to build the columns from.
     */
    private function applyColumns(string &$query, BaseTable $data) : void
    {
        $query .= $data->map(function($value) {
                // Handle raw text (non-columns).
                if ($this->action === 'COUNT' || $value === '*') {
                    return $value;
                }

                // handle sub-queries.
                if ($value instanceof SelectSubQuery) {
                    return $value->get();
                }

                // defaults to columns.
                return "`$value`";
            })
            ->implode(', ') . ' ';
    }

    /**
     * Applies the ORDER BY part of the query
     * 
     * @param string &$query The query that's being built.
     */
    private function applyOrder(string &$query) : void
    {
        if ($this->order->isNotEmpty()) {
            $query .= 'ORDER BY ';

            $query .= $this->order->implode(', ') . ' ';
        }
    }

    /**
     * Applies the ORDER BY part of the query
     * 
     * @param string &$query The query that's being built.
     */
    private function applyGroup(string &$query, BaseTable $bindings) : void
    {
        if ($this->group->isNotEmpty()) {
            $query .= 'GROUP BY ' . $this->group->implode(', ') . ' ';

            if (!is_null($this->having)) {
                // Apply the HAVING statement
                $query .= 'HAVING ' . $this->having->get();

                $bindings->merge($this->having->bindings());
            }
        }
    }

    /**
     * Applies the LIMIT part of the query
     * 
     * @param string &$query The query that's being built.
     */
    private function applyLimit(string &$query) : void
    {
        if (!is_null($this->limit)) {
            $query .= 'LIMIT ';

            if ($this->action == 'SELECT') {
                $query .= $this->offset . ', ';
            }

            $query .= $this->limit . ' ';
        }
    }

    /**
     * Runs the database query and returns the results in array form.
     *
     * @param string    $query    The query string to execute
     * @param BaseTable $bindings The bindings to apply
     *
     * @return mysqli_result|int|false
     * @throws QueryException
     */
    private function runQuery(string $query, BaseTable $bindings)
    {
        $db = Db::getInstance();

        $statement = $db->prepare($query);

        if (!$statement) {
            // there was an issue preparing the statement
            throw new QueryException('There was a problem preparing the statement. Error: ' . $db->error() . '. Query: ' . $query);
        }

        try {
            $response = $db->query($statement, $bindings->all());
        } catch (Exception $ex) {
            // There was an issue running the statement
            throw new QueryException('There was a problem executing the query. Error: ' . $ex->getMessage() . ', Query: ' . $query . ', bindings: ' . $bindings->toJson());
        }

        if ($response === true) {
            // that could mean it's freshly inserted, or columns have been updated.
            if ($db->insertedId) {
                return $db->insertedId;
            }
            return $db->count;
        }

        return $response;
    }

    /**
     * Attempts to get a list of all rows from the response
     * 
     * @param mysqli_result $response
     * 
     * @return Table|BaseTable Which may be empty if there are no results.
     */
    private function getResults(mysqli_result $response) : BaseTable
    {
        // Save the results into a temporary array
        $rows = [];
        while ($row = $response->fetch_assoc()) {
            if (isset($this->model) && $this->action !== 'COUNT') {
                $rows[] = new $this->model($row);
            } else {
                $rows[] = $row;
            }
        }

        // Reset (clear) the response
        $response->free();

        if (isset($this->model) && $this->action !== 'COUNT') {
            return Table::make($rows);
        } else {
            return table($rows);
        }
    }

    /**
     * Dumps the state and dies.
     */
    public function dd()
    {
        dd($this, $this->generateQuery());
    }
}