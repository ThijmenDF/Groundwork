<?php

namespace Groundwork\Migration\Builders;

use Groundwork\Utils\Table;

class Blueprint implements Builder {

    protected string $table;

    protected Table $columns;

    protected Table $dropItems;
    
    protected ?string $primary = null;

    protected Table $keys;

    public function __construct(string $table)
    {
        $this->table = $table;

        $this->columns = table();
        $this->dropItems = table();
        $this->keys = table();
    }

    /**
     * @param string $action
     *
     * @return Table
     */
    public function build(string $action = 'create') : Table
    {
        switch ($action) {
            case 'create':
                return $this->createTable();
            case 'alter':
                return $this->alterTable();
            case 'drop':
                return $this->dropTable();
            case 'dropIfExists':
                return $this->dropTable(true);
            case 'truncate':
                return $this->truncateTable();
        }

        // default action = no action
        return table();
    }

    /**
     * Builds a query statement to alter an existing table.
     *
     * @return Table
     */
    protected function alterTable() : Table
    {
        $str = "ALTER TABLE `$this->table`\n    ";

        $actions = table();

        $actions->merge($this->dropItems->map(fn (string $name) => "DROP $name"));

        // build the columns
        $actions->merge($this->columns->map(fn (Column $column) => "ADD COLUMN " . $column->build()));

        if (!is_null($this->primary)) {
            $actions->push("ADD PRIMARY KEY (`$this->primary`)");
        }

        // Build the keys (if any exist)
        if ($this->keys->isNotEmpty()) {
            $actions->merge($this->keys->map(fn(Key $key) => "ADD " . $key->build()));
        }

        $str .= $actions->implode(",\n    ") . ';';

        return table($str);
    }

    /**
     * Builds a query statement to make a new table.
     *
     * @return Table
     */
    protected function createTable() : Table
    {
        $str = "CREATE TABLE `$this->table` (\n    ";

        $actions = table();

        // build the columns
        $actions->merge($this->columns->map(fn (Column $column) => $column->build()));

        if (!is_null($this->primary)) {
            $actions->push("PRIMARY KEY (`$this->primary`)");
        }

        // Build the keys (if any exist)
        if ($this->keys->isNotEmpty()) {
            $actions->merge($this->keys->map(fn(Key $key) => $key->build()));
        }

        $str .= $actions->implode(",\n    ") . "\n) ENGINE=INNODB;";

        return table($str);
    }

    /**
     * Builds a new query statement to drop a table.
     *
     * @param bool $ifExists Whether to include the 'IF EXISTS' keyword in the query.
     *
     * @return Table
     */
    protected function dropTable(bool $ifExists = false) : Table
    {
        $str = "DROP TABLE " . ($ifExists ? 'IF EXISTS ' : '') . "`$this->table`;";

        return table($str);
    }

    /**
     * Builds a new query statement to truncate a table.
     * 
     * @return Table
     */
    protected function truncateTable() : Table
    {
        $str = "TRUNCATE TABLE `$this->table`";

        return table($str);
    }

    /**
     * Adds a new column
     *
     * @param string           $name        The column name
     * @param string           $type        The column type
     * @param bool             $null        Whether the column is nullable
     * @param string|bool|null $default     The table's default value. May be `null` to signify the actual 'NULL' value. It may
     *                                      also be `false` to not have a default value at all.
     * @param string|null      $extra       Extra data for the column
     *
     * @deprecated use the Column builder instead.
     */
    public function column(string $name, string $type, bool $null = true, $default = null, string $extra = null)
    {
        $column = "\t`$name` $type" . ($null ? ' NOT' : '') . " NULL";

        if (is_null($default)) {
            $column .= " DEFAULT NULL";
        }
        elseif (is_string($default)) {
            $column .= " DEFAULT $default";
        }

        if (!is_null($extra)) {
            $column .= " $extra";
        }

        $this->columns->push($column);
    }

    /**
     * Adds a primary key
     * 
     * @param string $column
     */
    public function primaryKey(string $column)
    {
        $this->primary = $column;
    }

    /**
     * Adds a unique key with one or more columns. Columns may be omitted, in which case $name will be the 
     * column and the name will have _unique appended to it.
     * 
     * @param string    $name       The unique name for this key   
     * @param string    $columns    The column(s) for the unique key
     * 
     * @return Key
     */
    public function unique(string $name, ...$columns) : Key
    {
        if (count($columns) === 0) {
            $columns[] = $name;
            $name .= '_unique';
        }

        $key = new Key($name);

        $this->keys->push($key);

        $key->unique(...$columns);

        return $key;
    }

    /**
     * Adds a foreign key
     *
     * @param string $name The key name
     * @param string $column
     *
     * @return Key
     */
    public function foreign(string $name, string $column) : Key
    {
        $key = new Key($name);

        $key->foreign($column);

        $this->keys->push($key);

        return $key;
    }

    /**
     * Creates a new column and returns it for editing
     * 
     * @param string $name
     * @param string $type
     * 
     * @return Column
     */
    private function createColumn(string $name, string $type) : Column
    {
        $column = new Column($name, $type);
        
        $this->columns->push($column);

        return $column;
    }

    /**
     * A basic unsigned integer that serves as the primary ID
     *
     * @param string $name
     *
     * @return Column
     */
    public function id(string $name = 'id') : Column
    {
        $this->primaryKey($name);

        return $this->uint($name)->append('AUTO_INCREMENT');
    }

    /**
     * Creates a nullable unsigned integer and a foreign key on it. Returns the foreign key builder.
     * 
     * @param string      $column 
     * @param string|null $name   (optional)
     * 
     * @return Key
     */
    public function foreignId(string $column, string $name = null) : Key
    {
        if (is_null($name)) {
            $name = $column . "_foreign";
        }

        $this->uint($column);

        return $this->foreign($name, $column);
    }
    
    /*
    * +----------------------------------------+
    * |                                        |
    * |                INTEGERS                |
    * |                                        |
    * +----------------------------------------+
    */
    
    /**
     * A basic signed integer
     * 
     * @param string $name
     * @param int    $size
     * 
     * @return Column
     */
    public function int(string $name, int $size = 11) : Column
    {
        return $this->createColumn($name, "INT($size)");
    }

    /**
     * A basic unsigned integer
     * 
     * @param string $name
     * @param int    $size
     * 
     * @return Column
     */
    public function uInt(string $name, int $size = 11) : Column
    {
        return $this->int($name, $size)->unsigned();
    }

    /**
     * A tiny signed integer (-128 - 127)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function tinyInt(string $name) : Column
    {
        return $this->createColumn($name, "TINYINT");
    }

    /**
     * A tiny unsigned integer (0 - 255)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function uTinyInt(string $name) : Column
    {
        return $this->tinyInt($name)->unsigned();
    }

    /**
     * A small signed integer (-32.768 - 32.767)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function smallInt(string $name) : Column
    {
        return $this->createColumn($name, "SMALLINT");
    }

    /**
     * A small unsigned integer (0 - 65.535)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function uSmallInt(string $name) : Column
    {
        return $this->smallInt($name)->unsigned();
    }

    /**
     * A medium signed integer (-8.388.608 - 8.388.607)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function mediumInt(string $name) : Column
    {
        return $this->createColumn($name, "MEDIUMINT");
    }

    /**
     * A medium unsigned integer (0 - 16.777.215)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function uMediumInt(string $name) : Column
    {
        return $this->mediumInt($name)->unsigned();
    }

    /**
     * A big signed integer (-9.223.372.036.854.775.808 - 9.223.372.036.854.775.807)
     * 
     * @param string $name
     * @param int    $size
     * 
     * @return Column
     */
    public function bigInt(string $name, int $size = 20) : Column
    {
        return $this->createColumn($name, "BIGINT($size)");
    }

    /**
     * A big unsigned integer (0 - 18.446.744.073.709.551.615)
     * 
     * @param string $name
     * @param int    $size
     * 
     * @return Column
     */
    public function uBigInt(string $name, int $size = 20) : Column
    {
        return $this->bigInt($name, $size)->unsigned();
    }

    /**
     * A decimal with fixed comma
     * 
     * @param string name
     * @param int    $numbers  (before the comma) (max 65)
     * @param int    $decimals (after the comma)  (max 30)
     * 
     * @return Column
     */
    public function decimal(string $name, int $numbers = 10, int $decimals = 0) : Column
    {
        return $this->createColumn($name, "DECIMAL($numbers, $decimals)");
    }

    /**
     * A float with variable comma. (3.402823466E+38 - 1.175494351E-38 both negative and positive, or 0)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function float(string $name) : Column
    {
        return $this->createColumn($name, "FLOAT");
    }

    /**
     * Similar to a float type but with double the precision. (1.7976931348623157E+308 - 2.2250738585072014E-308 
     * both negative and positive, or 0.)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function double(string $name) : Column
    {
        return $this->createColumn($name, "DOUBLE");
    }

    /**
     * Stores bits. Defaults to 1 bit, max 64
     * 
     * @param string $name
     * @param int    $length
     * 
     * @return Column
     */
    public function bit(string $name, int $length = 1) : Column
    {
        return $this->createColumn($name, "BIT($length)");
    }

    /**
     * Creates a boolean field, which is basically an TINYINT(1). 
     * 
     * 0 is considered as false, while 1 is considered as true.
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function boolean(string $name) : Column
    {
        return $this->createColumn($name, "BOOLEAN")->default(0);
    }

    /*
    * +----------------------------------------+
    * |                                        |
    * |             DATE AND TIME              |
    * |                                        |
    * +----------------------------------------+
    */

    /**
     * A date (1000-01-01 - 9999-12-31)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function date(string $name) : Column
    {
        return $this->createColumn($name, "DATE");
    }

    /**
     * A full datetime (1000-01-01 00:00:00 - 9999-12-31 23:59:59)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function datetime(string $name) : Column
    {
        return $this->createColumn($name, "DATETIME");
    }

    /**
     * A specific amount of hours, minutes and seconds. (-838:59:59 - 838:59:59)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function time(string $name) : Column
    {
        return $this->createColumn($name, "TIME");
    }

    /**
     * A 32-bit timestamp in seconds since epoch. (1970-01-01 00:00:01 - 2038-01-09 03:14:07)
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function timestamp(string $name) : Column
    {
        return $this->createColumn($name, "TIMESTAMP");
    }

    /**
     * Creates created_at and updated_at columns.
     */
    public function timestamps()
    {
        $this->createColumn('created_at', 'DATETIME')
            ->defaultCurrentTimestamp();
        
        $this->createColumn('updated_at', 'DATETIME')
            ->defaultCurrentTimestamp()
            ->updateCurrentTimestamp();
    }

    /**
     * Creates deleted_at column.
     */
    public function softDeletes()
    {
        $this->createColumn('deleted_at', 'DATETIME')
            ->nullable();
    }

    /*
    * +----------------------------------------+
    * |                                        |
    * |           STRINGS AND BLOBS            |
    * |                                        |
    * +----------------------------------------+
    */
    
    /**
     * A string with a fixed size. Smaller entries are padded with spaces on the right. (0 - 255)
     * 
     * @param string $name
     * @param int    $length 0 - 255
     * 
     * @return Column
     */
    public function char(string $name, int $length = 1) : Column
    {
        return $this->createColumn($name, "CHAR($length)");
    }

    /**
     * A string with variable size.
     * 
     * @param string $name
     * @param int    $length 0 - 65.535
     * 
     * @return Column
     */
    public function string(string $name, int $length = 255) : Column
    {
        return $this->createColumn($name, "VARCHAR($length)");
    }

    /**
     * A TEXT column with a maximum size of 2^8 - 1
     * 
     * @param string $name
     * @param int    $length 0 - 255
     * 
     * @return Column
     */
    public function tinyText(string $name, int $length = 255) : Column
    {
        return $this->createColumn($name, "TINYTEXT($length)");
    }

    /**
     * A TEXT column with a maximum size of 2^16 - 1
     * 
     * @param string $name
     * @param int    $length 0 - 65.535
     * 
     * @return Column
     */
    public function text(string $name, int $length = 255) : Column
    {
        return $this->createColumn($name, "TEXT($length)");
    }
    
    /**
     * A TEXT column with a maximum size of 2^24 - 1
     * 
     * @param string $name
     * @param int    $length 0 - 16.777.215
     * 
     * @return Column
     */
    public function mediumText(string $name, int $length = 255) : Column
    {
        return $this->createColumn($name, "MEDIUMTEXT($length)");
    }
    
    /**
     * A TEXT column with a maximum size of 2^32 - 1 (4gb)
     * 
     * @param string $name
     * @param int    $length 0 - 4.294.967.295
     * 
     * @return Column
     */
    public function longText(string $name, int $length = 255) : Column
    {
        return $this->createColumn($name, "LONGTEXT($length)");
    }

    /**
     * A BLOB column with a maximum size of 2^8 - 1
     * 
     * @param string $name
     * @param int    $length 0 - 255
     * 
     * @return Column
     */
    public function tinyBlob(string $name, int $length = 255) : Column
    {
        return $this->createColumn($name, "TINYBLOB($length)");
    }

    /**
     * A BLOB column with a maximum size of 2^16 - 1
     * 
     * @param string $name
     * @param int    $length 0 - 65.535
     * 
     * @return Column
     */
    public function blob(string $name, int $length = 65535) : Column
    {
        return $this->createColumn($name, "BLOB($length)");
    }
    
    /**
     * A BLOB column with a maximum size of 2^24 - 1
     * 
     * @param string $name
     * @param int    $length 0 - 16.777.215
     * 
     * @return Column
     */
    public function mediumBlob(string $name, int $length = 16777215) : Column
    {
        return $this->createColumn($name, "MEDIUMBLOB($length)");
    }
    
    /**
     * A BLOB column with a maximum size of 2^32 - 1 (4gb)
     * 
     * @param string $name
     * @param int    $length 0 - 4.294.967.295
     * 
     * @return Column
     */
    public function longBlob(string $name, int $length = 4294967295) : Column
    {
        return $this->createColumn($name, "LONGBLOB($length)");
    }

    /**
     * A JSON column
     * 
     * @param string $name
     * 
     * @return Column
     */
    public function json(string $name) : Column
    {
        return $this->createColumn($name, "JSON");
    }

    /*
    * +----------------------------------------+
    * |                                        |
    * |         OTHER / UNCATEGORIZED          |
    * |                                        |
    * +----------------------------------------+
    */

    /**
     * Creates two columns meant for polymorphic relationships.
     *
     * @param string $name      The name for the polymorphic relation. This name is appended with _type and _id.
     * @param bool   $nullable  If the relationship should be nullable.
     *
     * @return array
     */
    public function morph(string $name, bool $nullable = false) : array
    {
        $type = $this->string($name . '_type');
        $id = $this->uInt($name . '_id');

        if ($nullable) {
            $type->nullable();
            $id->nullable();
        }

        return [$type, $id];
    }

    /*
    * +----------------------------------------+
    * |                                        |
    * |         DROPPING AND REMOVING          |
    * |                                        |
    * +----------------------------------------+
    */

    /**
     * Sets up a column to drop.
     * 
     * @param string $column
     */
    public function dropColumn(string $column)
    {
        $this->dropItems->push("COLUMN $column");
    }

    /**
     * Sets up a foreign key to drop.
     * 
     * @param string $name
     */
    public function dropForeign(string $name)
    {
        $this->dropItems->push("FOREIGN KEY $name");
    }

    /**
     * Sets up a unique index to drop.
     * 
     * @param string $name
     */
    public function dropUnique(string $name)
    {
        $this->dropItems->push("INDEX $name");
    }

    /**
     * Sets up the primary key to drop.
     */
    public function dropPrimaryKey()
    {
        $this->dropItems->push("PRIMARY KEY");
    }

}