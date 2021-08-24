<?php

namespace Groundwork\Migration\Builders;

use Groundwork\Utils\Table;

class Column implements Builder {
    
    protected Table $buildData;

    /**
     * Creates a new Column builder.
     * 
     * @param string $column The column name
     * @param string $type   The column type (as SQL)
     */
    public function __construct(string $column, string $type)
    {
        $this->buildData = table([
            'column' => $column,
            'type' => $type,
            'null' => false
        ]);
    }

    /**
     * Compiles the build data into an SQL string.
     * 
     * @return string
     */
    public function build() : string
    {
        $str = '`' . $this->buildData->get('column') . '` ' . $this->buildData->get('type');

        if ($this->buildData->has('unsigned') && $this->buildData->get('unsigned')) {
            $str .= ' UNSIGNED';
        }

        if ($this->buildData->has('update')) {
            $str .= ' on update ' . $this->buildData->get('update');
        }
        
        $str .= ($this->buildData->get('null') ? '' : ' NOT' ) . ' NULL';

        if ($this->buildData->has('default')) {
            $str .= ' DEFAULT ' . $this->buildData->get('default');
        }

        if ($this->buildData->has('after')) {
            $str .= ' AFTER `' . $this->buildData->get('after') . '`';
        }

        if ($this->buildData->has('append')) {
            $str .= ' ' . $this->buildData->get('append');
        }

        return $str;
    }

    /**
     * Sets the column as nullable.
     * 
     * @return self
     */
    public function nullable() : self
    {
        $this->buildData->set('null', true);
        $this->buildData->set('default', $this->buildData->get('default', 'NULL'));

        return $this;
    }

    /**
     * Sets the default value (as string).
     * 
     * @param string $default
     * 
     * @return self
     */
    public function default(string $default) : self
    {
        $this->buildData->set('default', "'$default'");

        return $this;
    }

    /**
     * Sets the default to be the current timestamp.
     * 
     * @return self
     */
    public function defaultCurrentTimestamp() : self
    {
        $this->buildData->set('default', 'CURRENT_TIMESTAMP');

        return $this;
    }

    /**
     * Sets the 'on update' to set the value as the current timestamp.
     * 
     * @return self
     */
    public function updateCurrentTimestamp() : self
    {
        $this->buildData->set('update', 'CURRENT_TIMESTAMP');

        return $this;
    }

    /**
     * Sets the column to be unsigned.
     * 
     * @return self
     */
    public function unsigned() : self
    {
        $this->buildData->set('unsigned', true);

        return $this;
    }

    /**
     * Sets after which column this column should be inserted.
     * 
     * @param string $column
     * 
     * @return self
     */
    public function after(string $column) : self
    {
        $this->buildData->set('after', $column);

        return $this;
    }

    /**
     * Adds more data to the SQL query at the end.
     * 
     * @param string $data
     * 
     * @return self
     */
    public function append(string $data) : self
    {
        $this->buildData->set('append', $data);

        return $this;
    }

    

}