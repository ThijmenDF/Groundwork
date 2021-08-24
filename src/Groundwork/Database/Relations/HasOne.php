<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Table;
use Groundwork\Database\Model;
use Groundwork\Database\Relations\Interfaces\Associatable;
use Groundwork\Database\Relations\Interfaces\Savable;

class HasOne extends Relation implements Savable, Associatable
{
    protected string $foreignKey;
    protected ?string $localKey;

    public function __construct(Model $parent, string $model, string $foreignKey, string $localKey = null)
    {
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        parent::__construct($parent, new $model);
    }

    public function setQuery()
    {
        $this->where(
            $this->foreignKey,
            $this->parent->{$this->localKey ?? $this->parent->getIdentifierKey()}
        )
            ->limit(); // it's called 'hasONE' after all.
    }

    public function processResult(Table $results) : ?Model
    {
        return $results->first();
    }

    public function save(Model $model) : bool
    {
        $this->associate($model);

        return $model->save();
    }

    public function associate(Model $model) : bool
    {
        $model->{$this->foreignKey} = $this->parent->{$this->localKey ?? $this->parent->getIdentifierKey()};

        return true;
    }

    public function dissociate(Model $model = null) : bool
    {
        if (is_null($model)) {
            return false;
        }

        $model->{$this->foreignKey} = null;

        return true;
    }
}