<?php

namespace Groundwork\Migration\Builders;

class Key implements Builder {

    protected string $name;

    protected string $type;

    protected string $references;

    protected string $onDelete = 'RESTRICT';

    protected string $onUpdate = 'RESTRICT';

    public function __construct($name)
    {
        $this->name = $name;
    }


    public function build() : string
    {
        $str = "CONSTRAINT `$this->name` \n    ". $this->type;

        if (isset($this->references)) {
            $str .= "\n    " . $this->references . "\n    ON DELETE $this->onDelete\n    ON UPDATE $this->onUpdate";
        }

        return $str;
    }

    public function unique(string ...$columns)
    {
        $this->type = 'UNIQUE (`' . implode('`, `', $columns) . '`)';
    }

    public function foreign(string $column) : self
    {
        $this->type = "FOREIGN KEY (`$column`)";

        return $this;
    }

    public function references(string $table, string $column) : self
    {
        $this->references = "REFERENCES `$table` (`$column`)";

        return $this;
    }

    public function cascadeOnDelete() : self
    {
        $this->onDelete = 'CASCADE';

        return $this;
    }

    public function nullOnDelete() : self
    {
        $this->onDelete = 'SET NULL';

        return $this;
    }

    public function restrictOnDelete() : self
    {
        $this->onDelete = 'RESTRICT';

        return $this;
    }

    public function cascadeOnUpdate() : self
    {
        $this->onUpdate = 'CASCADE';

        return $this;
    }

    public function nullOnUpdate() : self
    {
        $this->onUpdate = 'SET NULL';

        return $this;
    }

    public function restrictOnUpdate() : self
    {
        $this->onUpdate = 'RESTRICT';

        return $this;
    }

}