<?php

namespace Groundwork\Database\Relations\Interfaces;

use Groundwork\Database\Model;

interface Associatable
{
    /**
     * Associates the given model with the parent model.
     *
     * @param Model $model
     *
     * @return bool
     */
    public function associate(Model $model) : bool;

    /**
     * Removes the association between the parent model, and it's related model.
     *
     * @param Model|null $model
     *
     * @return bool
     */
    public function dissociate(Model $model = null) : bool;
}