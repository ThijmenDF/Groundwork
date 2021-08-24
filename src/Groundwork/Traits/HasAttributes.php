<?php

namespace Groundwork\Traits;

use Carbon\Carbon;
use Groundwork\Exceptions\Database\QueryException;
use Groundwork\Database\Relations\Relation;

/**
 * Adds the properties and methods that allow attributes to dynamically get added and compared to an earlier state.
 */
trait HasAttributes {

    /**
     * The key for the Serializable trait
     */
    private string $serializeKey = 'attributes';
    
    /**
     * The current state of the attributes. This may change in runtime
     */
    protected array $attributes = [];

    /**
     * The original state of the attributes. This can only change in the 'fill' method and is used to compare for
     * changes
     */
    protected array $original = [];

    /**
     * Indicates whether the model is 'fresh' and has not been saved yet.
     */
    protected bool $fresh = false;

    /**
     * Attempts to get a specific item from the models attributes array.
     *
     * Otherwise, if a method exists with that name which returns a Relationship, it will fetch that relationship.
     *
     * @param string|int $key The key or attribute name
     *
     * @return mixed The value or relation model, or null if not found.
     */
    public function __get($key)
    {
        if ($this->hasAttribute($key)) {
            return $this->attributes[$key];
        }

        // check for relation methods with this name
        if (method_exists($this, $key)) {
            if (isset($this->relations[$key])) {
                return $this->relations[$key];
            }

            $result = call_user_func([$this, $key]);

            if ($result instanceof Relation) {
                // handle the relationship
                try {
                    $query = $result->get();
                    return $this->relations[$key] = $result->processResult($query);
                } catch (QueryException $exception) {
                    // do nothing.
                }
            }
        }

        return null;
    }

    /**
     * Sets a specific data point in the models attributes array
     * 
     * @param string|int $key   The key or attribute name
     * @param mixed      $value The value to set
     * 
     * @return bool
     */
    public function __set($key, $value) : bool
    {
        $this->attributes[$key] = $value;

        if ($this->hasDates) {
            $this->attributes['updated_at'] = Carbon::now();
        }

        return true;
    }

    /**
     * Returns whether a specific key exists.
     * 
     * @param mixed $key
     * 
     * @return bool
     */
    public function __isset($key) : bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Return whether a specific attribute has changed (compared to original)
     * 
     * @param string|int $key The item to check
     * 
     * @return bool
     */
    public function changed($key) : bool
    {
        return $this->fresh || ($this->attributes[$key] ?? null) !== ($this->original[$key] ?? null);
    }

    /**
     * If the attributes array has changed at all. Order does not matter, only keys/values
     * 
     * @return bool
     */
    public function dirty() : bool
    {
        return $this->fresh || $this->attributes != $this->original;
    }

    /**
     * Returns the original version of an attribute.
     *
     * @param string|null $key
     *
     * @return array|mixed|null
     */
    public function getOriginal(string $key = null)
    {
        if (is_null($key)) {
            return $this->original;
        }

        if ($this->has($key)) {
            return $this->original[$key];
        }

        return null;
    }

    /**
     * Returns whether the attributes have a specific key
     * 
     * @param mixed $key
     * 
     * @return bool
     */
    public function hasAttribute($key) : bool
    {
        return $this->__isset($key);
    }

    /**
     * Returns all attributes as an array.
     * 
     * @return array
     */
    public function allAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * Create a list of all changed attributes.
     * 
     * @return array
     */
    public function getAllChanges() : array
    {
        if ($this->fresh) {
            return $this->allAttributes();
        }

        $changes = [];
        foreach ($this->allAttributes() as $key => $value) {
            // See if this key has been changed.
            if ($this->changed($key)) {
                // save it to the changes array.
                $changes[$key] = $value;
            }
        }
        return $changes;
    }

    /**
     * Sets up the attributes and original arrays
     * 
     * @param array $data The dataset
     */
    protected function fill(array $data) : void
    {
        // Reset the list first
        $this->attributes = [];

        // Saves the values into the attributes array.
        foreach ($data as $key => $item) {
            $this->attributes[$key] = $item;
        }

        // Save a copy of the original data, so we can detect changes
        $this->original = $this->attributes;
    }
}