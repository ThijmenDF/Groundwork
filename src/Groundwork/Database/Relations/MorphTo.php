<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Table;
use Groundwork\Database\Model;
use Groundwork\Database\Relations\Interfaces\Associatable;

class MorphTo extends Relation implements Associatable
{
    protected string $morphKey;

    public function __construct(Model $parent, string $morphKey)
    {
        $this->morphKey = $morphKey;

        parent::__construct($parent, new $parent->{$morphKey . '_type'});
}

    public function setQuery()
    {
        // it's basically a hasOne relation
        $this->where($this->model->getIdentifierKey(), $this->parent->{$this->morphKey . '_id'});
    }

    public function processResult(Table $results) : ?Model
    {
        return $results->first();
    }

    public function associate(Model $model) : bool
    {
        $this->parent->{$this->morphKey . '_type'} = get_class($model);
        $this->parent->{$this->morphKey . '_id'} = $model->getIdentifier();

        return true;
    }

    public function dissociate(Model $model = null) : bool
    {
        $this->parent->{$this->morphKey . '_type'} = null;
        $this->parent->{$this->morphKey . '_id'} = null;

        return true;
    }
}