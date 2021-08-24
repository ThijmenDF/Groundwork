<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Table;
use Groundwork\Database\Model;
use Groundwork\Database\Relations\Interfaces\Associatable;

class BelongsTo extends Relation implements Associatable
{
    protected string $foreignKey;
    protected ?string $ownerKey;

    public function __construct(Model $parent, string $model, string $foreignKey, string $ownerKey = null)
    {
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;

        parent::__construct($parent, new $model);
    }

    public function setQuery()
    {
        $this->where(
            $this->ownerKey ?? $this->model->getIdentifierKey(),
            $this->parent->{$this->foreignKey}
        )
            ->limit();
    }

    public function processResult(Table $results) : ?Model
    {
        return $results->first();
    }

    public function associate(Model $model) : bool
    {
        // associate the parent model with the given model
        $this->parent->{$this->foreignKey} = $model->{$this->ownerKey ?? $this->model->getIdentifierKey()};

        return true;
    }

    public function dissociate(Model $model = null) : bool
    {
        // Remove any associations. This may cause an exception if the foreign key isn't nullable!
        $this->parent->{$this->foreignKey} = null;

        return true;
    }
}