<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Query;
use Groundwork\Exceptions\Database\QueryException;
use Groundwork\Database\Model;
use Groundwork\Database\Relations\Interfaces\Attachable;
use Groundwork\Database\Relations\Interfaces\Savable;
use Groundwork\Utils\Table;

class BelongsToMany extends Relation implements Savable, Attachable
{
    protected string $intermediateTable;
    protected string $foreignKey;
    protected ?string $localKey;

    public function __construct(Model $parent, string $model, string $intermediateTable, string $localKey, string $foreignKey = null)
    {
        $this->intermediateTable = $intermediateTable;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        parent::__construct($parent, new $model);
    }

    public function setQuery()
    {
        // @todo: implement this as a sort of sub-query or join

        $intermediate = $this->getAttachedEntries();

        // Then load the items from the model
        $this->whereIn(
            $this->model->getIdentifierKey(),
            $intermediate->all()
        );
    }

    public function save(Model $model) : bool
    {
        // two things need to happen:
        // 1. save the model
        // 2. use the model's ID in the intermediate table.
        if (!$model->save()) {
            return false;
        }

        $id = $model->getIdentifier();

        return $this->attach($id);
    }

    public function attach($ids) : bool
    {
        $ids = table($ids);

        $attached = $this->getAttachedEntries();

        $notAttached = $ids->diff($attached);

        return $notAttached->every(function (int $id) {
            return !!(new Query($this->intermediateTable))
                ->insert([
                    $this->localKey => $this->parent->getIdentifier(),
                    $this->foreignKey => $id
                ]);
        });
    }

    public function detach($ids) : bool
    {
        $ids = table($ids);

        return !!(new Query($this->intermediateTable))
            ->whereIn($this->foreignKey, $ids->all())
            ->delete();
    }

    public function sync($ids) : bool
    {
        $ids = table($ids);

        $attached = $this->getAttachedEntries();

        $deprecated = $attached->diff($ids); // these ids are missing from $ids (they need to be removed)
        $missing = $ids->diff($attached); // these ids are missing from $attached (they need to be added)

        return $this->detach($deprecated->all()) && $this->attach($missing->all());
    }

    public function toggle($ids) : bool
    {
        $ids = table($ids);

        $attached = $this->getAttachedEntries();

        // if they're missing, they need to be added
        $missing = $ids->diff($attached); // These ids are missing from $attached (they need to be added)

        // if they exist, they need to be removed
        $exists = $attached->filter(fn (int $item) => $ids->contains($item));

        return $this->detach($exists->all()) && $this->attach($missing->all());
    }

    /**
     * Gets the list of all attached ID's
     *
     * @return Table
     */
    private function getAttachedEntries() : Table
    {
        try {
            // First load all items from the intermediate table
            return (new Query($this->intermediateTable))
                ->where($this->localKey, $this->parent->getIdentifier())
                ->get($this->foreignKey)
                ->pluck($this->foreignKey)
                ->transform(fn($item) => (int) $item);
        } catch (QueryException $exception) {
            return table();
        }
    }
}