<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Model;
use Groundwork\Database\Relations\Interfaces\Associatable;
use Groundwork\Database\Relations\Interfaces\Savable;

class MorphMany extends Relation implements Savable, Associatable
{
    protected string $morphKey;

    public function __construct(Model $parent, string $model, string $morphKey)
    {
        $this->morphKey = $morphKey;

        parent::__construct($parent, new $model);
    }

    public function setQuery()
    {
        // it's basically a hasOne relation
        $this->where($this->morphKey . '_type', get_class($this->parent))
            ->where($this->morphKey . '_id', $this->parent->getIdentifier());
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
        if (is_null($model)) {
            return false;
        }

        $model->{$this->morphKey . '_type'} = null;
        $model->{$this->morphKey . '_id'} = null;

        return true;
    }
}