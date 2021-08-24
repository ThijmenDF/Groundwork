<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Query;
use Groundwork\Database\Table;
use Groundwork\Database\Model;
use Groundwork\Database\Relations\Interfaces\Savable;

class Relation extends Query
{
    protected Model $parent;
    protected Model $targetModel;

    public function __construct(Model $parent, Model $model)
    {
        $this->parent = $parent;
        $this->targetModel = $model;

        parent::__construct($model);

        $this->setQuery();
    }

    /**
     * A unique action per relation type which sets up the correct 'where' statements on the target model.
     */
    public function setQuery() {}

    /**
     * Takes the incoming result set and extracts the required data. By default, this method will return the result set
     * as-is. This action can be overwritten by specific relations to give back a specific singular model instead.
     *
     * @param Table $results
     *
     * @return Table|Model
     */
    public function processResult(Table $results)
    {
        return $results;
    }

    /**
     * Attempts to create a new model with the given data based on the target model of the relation.
     *
     * If the relation is not savable, it can not create the model and will return null.
     *
     * @param array|Table $data
     *
     * @return Model|null
     */
    public function create($data = []) : ?Model
    {
        // This is a basic version of the create method. If any relation required a different approach, they should
        // implement that in their own class.
        if ($this instanceof Savable) {
            $model = $this->targetModel::make($data);

            if ($this->save($model)) {
                return $model;
            }
        }

        return null;
    }

}