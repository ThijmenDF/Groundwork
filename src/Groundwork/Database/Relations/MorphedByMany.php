<?php

namespace Groundwork\Database\Relations;

use Groundwork\Database\Query;
use Groundwork\Exceptions\Database\QueryException;
use Groundwork\Database\Model;
use Groundwork\Database\Relations\Interfaces\Attachable;
use Groundwork\Utils\Table;

class MorphedByMany extends Relation implements Attachable
{
    protected string $localKey;
    protected string $morphKey;
    protected string $intermediate;

    public function __construct(Model $parent, string $model, string $localKey, string $morphKey, string $intermediate)
    {
        $this->localKey = $localKey;
        $this->morphKey = $morphKey;
        $this->intermediate = $intermediate;

        parent::__construct($parent, new $model);
    }

    public function setQuery()
    {
        // get all entries in the intermediate table that reference this model and the target model
        $intermediate = $this->getAttachedEntries();

        $this->whereIn(
            $this->model->getIdentifierKey(),
            $intermediate->all()
        );
    }

    public function attach($ids) : bool
    {
        $ids = table($ids);

        $attached = $this->getAttachedEntries();

        $notAttached = $ids->diff($attached);

        return $notAttached->every(function (int $id) {
            return !!(new Query($this->intermediate))
                ->insert([
                    $this->morphKey . '_type' => get_class($this->targetModel),
                    $this->morphKey . '_id' => $id,
                    $this->localKey => $this->parent->getIdentifier()
                ]);
        });
    }

    public function detach($ids) : bool
    {
        $ids = table($ids);

        return !!(new Query($this->intermediate))
            ->whereIn($this->localKey, $ids->all())
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
        // First load all items from the intermediate table
        try {
            return (new Query($this->intermediate))
                ->where($this->localKey, $this->parent->getIdentifier())
                ->where($this->morphKey . '_type', get_class($this->model))
                ->get($this->morphKey . '_id')
                ->pluck($this->morphKey . '_id')
                ->transform(fn($item) => (int) $item);
        } catch (QueryException $e) {
            return table();
        }
    }
}