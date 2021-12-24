<?php

namespace Groundwork\Database;

use Groundwork\Config\Config;
use Groundwork\Exceptions\Database\ConnectionException;
use Groundwork\Exceptions\Database\DatabaseException;
use Groundwork\Exceptions\Database\QueryBindingException;
use Groundwork\Exceptions\Database\QueryException;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use stdClass;

/**
 * MySQLConnection Class.
 * Adds various functions for easy database connectivity.
 */
class Db {
    
    private mysqli $conn;
    private ?mysqli_result $result = null;
    
    protected bool $connected = false;
    
    public int $count = 0;
    public ?int $insertedId = null;
    
    /**
     * Constructs a new mysqli connection.
     * @throws ConnectionException when it's unable to connect to the database
     */
    public function __construct()
    {
        $this->conn = new mysqli(
            Config::get('DB_HOST', 'localhost'),
            Config::get('DB_USER', 'root'),
            Config::get('DB_PASS'),
            Config::get('DB_NAME'),
            Config::get('DB_PORT', 3306)
        );
        
        if (! $this->conn->connect_error) {
            $this->connected = true;
        }
        else {
            throw new ConnectionException("Unable to connect to the database - " . $this->conn->connect_error, $this->conn->connect_errno);
        }
    }

    public function __destruct() 
    {
        $this->close();
    }
    
    /**
     * Closes the connection if it was open
     */
    public function close() 
    {
        if($this->connected) {
            $this->conn->close();
            $this->connected = false;
        }
    }

    /**
     * Returns whether the database is connected
     * @return bool
     */
    public function isConnected() : bool
    {
        return $this->connected;
    }

    /**
     * Creates a new prepared statement that can be passed into `query`
     *
     * @param string $query
     *
     * @return null|mysqli_stmt
     */
    public function prepare(string $query) : ?mysqli_stmt
    {
        if (! $this->connected) {return null;}

        $statement = $this->conn->prepare($query);

        if ($statement) {
            return $statement;
        }

        return null;
    }

    /**
     * Runs a mysql query on the database
     *
     * @param mysqli_stmt $statement
     * @param array       $params [optional] The data to replace ?'s with
     *
     * @return null|mysqli_result|bool
     * @throws DatabaseException
     */
    public function query(mysqli_stmt $statement, array $params = [])
    {
        if (! $this->connected) {return null;}

        // Only bind when there are params
        if (count($params)) {
            // Get the variable types
            $types = '';

            foreach ($params as $param) {
                switch (true) {
                    case is_float($param):
                        $types .= 'd'; // decimal
                        break;
                    case is_numeric($param):
                        $types .= 'i'; // integer
                        break;
                    default:
                        $types .= 's'; // string
                }
            }

            // Bind the values
            if (!$statement->bind_param($types, ...$params)) {
                throw new QueryBindingException("Unable to bind statement params. types: " . $types);
            }
        }
        
        // Execute the query
        if(!$statement->execute()) { 
            $this->count = 0;
            $this->result = null;

            throw new QueryException($statement->error, $statement->errno);
        }

        $result = $statement->get_result();

        if ($result === false) {// There are no results
            $this->count = $statement->affected_rows;
            $this->insertedId = $statement->insert_id;

            $result = true;
        } else { // There are results (not false)
            $this->count = $result->num_rows;
            $this->insertedId = null;
            $this->result = $result;
        }

        $statement->free_result();

        $statement->close();

        return $result;
    }

    /**
     * Runs a pure SQL query on the database.
     *
     * **! This method is insecure. Please use prepared statements if possible !**
     *
     * @param string $query
     *
     * @return mysqli_result|null|bool
     */
    public function raw(string $query)
    {
        if (!$this->isConnected()) {
            return null;
        }

        $result = $this->conn->query($query);

        if (is_bool($result)) {
            if ($result === true) {
                $this->count = $this->conn->affected_rows;
                $this->insertedId = $this->conn->insert_id;
            } else {
                $this->count = 0;
                $this->insertedId = null;
            }

            return $result;
        }

        $this->result = $result;

        $this->count = $result->num_rows;

        return $this->result;
    }

    /**
     * Fetches a single row from a result set
     * 
     * @param null|mysqli_result $result [optional] the result set. If not given it will use the most recent result set.
     * @param bool $asObject [optional] Whether to return as a stdClass
     * 
     * @return array|object|stdClass|null
     */
    public function row(mysqli_result $result = null, bool $asObject = false) 
    {
        if (is_null($result)) {
            $result = $this->result;
        }

        if (is_null($result)) {
            // The result is still null... there must have been an error or this was called before the first query
            return null;
        }

        if ($asObject) {
            return $result->fetch_object();
        }
        return $result->fetch_assoc();
    }

    /**
     * Fetches the most recently inserted AI ID or 0 if there's a connection or query failure
     * 
     * @return int
     */
    public function lastInsertedId() : int
    {
        return $this->insertedId;
    }

    /**
     * Returns the amount of rows acquired by the most recent query.
     *
     * @return int
     */
    public function count() : int
    {
        return $this->count;
    }

    /**
     * Returns the most recent mysqli error
     * 
     * @return string
     */
    public function error() : string
    {
        return $this->conn->error;
    }

    /**
     * Attempts to escape a string. Not required for prepared statements.
     * 
     * @param string $input
     * 
     * @return string
     */
    public function escape(string $input) : string
    {
        if(!$this->connected) {return "";}

        return $this->conn->real_escape_string($input);
    }
}