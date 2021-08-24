<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Query;
use Groundwork\Exceptions\Database\QueryException;
use Groundwork\Database\Model;
use Groundwork\Database\Relations\Interfaces\Attachable;
use Groundwork\Database\Relations\Interfaces\Savable;
use Groundwork\Utils\Table;

class MorphToMany extends Relation implements Savable, Attachable
{
    protected string $morphKey;
    protected string $modelKey;
    protected string $intermediate;

    public function __construct(Model $parent, string $model, string $modelKey, string $morphKey, string $intermediate)
    {
        $this->modelKey = $modelKey;
        $this->morphKey = $morphKey;
        $this->intermediate = $intermediate;

        parent::__construct($parent, new $model);
    }

    public function setQuery()
    {
        // @todo: use a join or sub-query once this is available.
        $intermediate = $this->getAttachedEntries();

        $this->whereIn(
            $this->model->getIdentifierKey(),
            $intermediate->all()
        );
    }

    public function save(Model $model) : bool
    {
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
            return !!(new Query($this->intermediate))
                ->insert([
                    $this->morphKey . '_type' => get_class($this->parent),
                    $this->morphKey . '_id' => $this->parent->getIdentifier(),
                    $this->modelKey => $id
                ]);
        });
    }

    public function detach($ids) : bool
    {
        $ids = table($ids);

        return !!(new Query($this->intermediate))
            ->whereIn($this->modelKey, $ids->all())
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
            return (new Query($this->intermediate))
                ->where($this->morphKey . '_id', $this->parent->getIdentifier())
                ->where($this->morphKey . '_type', get_class($this->parent))
                ->get($this->modelKey)
                ->pluck($this->modelKey)
                ->transform(fn($item) => (int) $item);
        } catch (QueryException $exception) {
            return table();
        }
    }
}