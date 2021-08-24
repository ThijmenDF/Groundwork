<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Table;
use Groundwork\Database\Model;
use Groundwork\Database\Relations\Interfaces\Associatable;
use Groundwork\Database\Relations\Interfaces\Savable;

class MorphOne extends Relation implements Savable, Associatable
{
    protected string $morphKey;

    public function __construct(Model $parent, string $model, string $morphKey)
    {
        $this->morphKey = $morphKey;

        parent::__construct($parent, new $model);
   }

    public function setQuery()
    {
        // it's basically a hasOne relation with an additional type
        $this->where($this->morphKey . '_type', get_class($this->parent))
            ->where($this->morphKey . '_id', $this->parent->getIdentifier())
            ->limit();
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
        $model->{$this->morphKey . '_type'} = get_class($this->parent);
        $model->{$this->morphKey . '_id'} = $this->parent->getIdentifier();

        return true;
    }

    public function dissociate(Model $model = null) : bool
    {
        // if it's null, fetch the associated model.
        if (is_null($model)) {
            $result = $this->get();

            $model = $this->processResult($result);
        }

        // if it's still null, don't do anything
        if (is_null($model)) {
            return false;
        }

        $model->{$this->morphKey . '_type'} = null;
        $model->{$this->morphKey . '_id'} = null;

        return true;
    }
}