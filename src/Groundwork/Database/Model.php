<?php

namespace Groundwork\Database;

use Carbon\Carbon;
use Carbon\Exceptions\UnknownMethodException;
use Groundwork\Exceptions\Database\DatabaseException;
use Groundwork\Exceptions\Database\QueryException;
use Groundwork\Exceptions\Http\NotFoundException;
use Groundwork\Injector\Injection;
use Groundwork\Migration\Builders\Schema;
use Groundwork\Database\Relations\RelationMethods;
use Groundwork\Traits\HasAttributes;
use Groundwork\Traits\Serializable;
use Groundwork\Utils\Str;
use Groundwork\Utils\Table as BaseTable;
use JsonSerializable;

/**
 * Base Model class. All database models should extend from here.
 * 
 * Has attributes and modification check features, and uses the `__get` and `__set` 
 * methods to dynamically change them.
 * 
 * Can be queried with `where` and `find` methods.
 * 
 * Can be saved to the database with the `save` method.
 * 
 * @mixin Table The Table class which runs on the attributes
 * @mixin Query The Query class which runs new queries
 */
class Model implements JsonSerializable, Injection
{

    use HasAttributes, Serializable, RelationMethods;

    /** The table name. Defaults to the class name (snake case) */
    protected string $table;

    /** The main Identifier (ID) key. Defaults to `id` */
    protected string $identifierKey = 'id';

    /** Whether the model uses updated_at and created_at */
    protected bool $hasDates = true;

    /** Whether the model gets soft-deleted. */
    protected bool $softDeletes = false;

    /**
     +--------------------------------------------+
     |                                            |
     |    Magic Methods                           |
     |                                            |
     +--------------------------------------------+
     */

    /**
     * Sets up a new instance of this model and fills in the data, if provided.
     *
     * @param array|BaseTable $data
     */
    public function __construct($data = [])
    {
        $data = table($data);
        // Set the data if given.
        if ($data->isNotEmpty()) {

            // Apply date fields if applicable.
            if ($this->hasDates) {
                $data->created_at = Carbon::make($data->created_at ?? Carbon::now());
                $data->updated_at = Carbon::make($data->updated_at ?? Carbon::now());
            }
            
            // Apply soft-deletes fields
            if ($this->softDeletes) {
                $data->deleted_at = $data->deleted_at ? Carbon::make($data->deleted_at) : null;
            }

            // Save it to the attributes
            $this->fill($data->all());
        }
    }
    
    /**
     * Handles methods getting called that do not exist in the class.
     * 
     * This method checks if it exists on the Table class. If it does, it runs the method with the model's attributes.
     * 
     * @param string $name
     * @param array  $arguments
     * 
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        // See if it is part of the Table first
        if (method_exists(Table::class, $name)) {
            $data = table($this->allAttributes());

            return call_user_func_array([$data, $name], $arguments);
        }

        // it's not, just throw an exception.
        throw new UnknownMethodException("$name on " . static::class);
    }

    /**
     * Handles methods getting statically called that do not exist in the class.
     * 
     * This method checks if it exists on the Query class. If it does, it runs the method with a new Query instance.
     * 
     * @param string $name
     * @param array  $arguments
     * 
     * @return Query
     * @throws UnknownMethodException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        // See if it is part of the Query class
        if (method_exists(Query::class, $name)) {
            $query = self::newQuery();
            return call_user_func_array([$query, $name], $arguments);
        }
        
        throw new UnknownMethodException("$name on " . static::class);
    }

    /**
     * Loads the model matching the ID.
     *
     * @param int $param The model ID.
     *
     * @return static The model
     * @throws NotFoundException if the model cannot be found
     */
    public static function __inject($param) : self
    {
        return static::findOrFail($param);
    }

    
    /**
     +--------------------------------------------+
     |                                            |
     |    Common Methods                          |
     |                                            |
     +--------------------------------------------+
     */

    /**
     * Creates a new instance of a model.
     * 
     * @param array|BaseTable $data
     * 
     * @return static
     */
    public static function make($data = []) : self
    {
        $model = new static($data);

        // Mark the model as 'fully dirty'
        $model->fresh = true;

        return $model;
    }

    /**
     * Creates a new instance of a model and immediately saves it to the database.
     *
     * @param array|BaseTable $data
     *
     * @return static
     */
    public static function create($data) : self
    {
        $model = static::make($data);

        $model->save();

        return $model;
    }
    
    /**
     * Returns the models Identifier value.
     * 
     * @return int|null
     */
    public function getIdentifier() : ?int
    {
        return $this->{$this->identifierKey};
    }

    /**
     * Sets the new identifier value
     * 
     * @param mixed $id
     */
    public function setIdentifier($id) : void
    {
        $this->{$this->identifierKey} = (int) $id;
    }

    /**
     * Returns the models Identifier key name.
     * 
     * @return string
     */
    public function getIdentifierKey() : string
    {
        return $this->identifierKey;
    }

    /**
     * Returns the model's database table.
     * 
     * @return string
     */
    public function getTable() : string
    {
        // Take the 'table' attribute, or the class name appended with 's' if not set.
        return $this->table ?? Str::snake(class_basename($this) . 's');
    }

    /**
     * Returns whether the model is soft-deleted.
     *
     * @return bool
     */
    public function softDeletes() : bool
    {
        return $this->softDeletes;
    }

    /**
     * Returns whether the model uses timestamps such as created_at and updated_at.
     *
     * @return bool
     */
    public function hasDates() : bool
    {
        return $this->hasDates;
    }

    /**
     +--------------------------------------------+
     |                                            |
     |    Query Methods                           |
     |                                            |
     +--------------------------------------------+
     */


    /**
     * Runs a simple 'where' query to find a model by its identifier.
     * 
     * @param mixed $id
     * 
     * @return static|null
     */
    public static function find($id) : ?self
    {
        $model = new static();

        return self::newQuery($model)->where($model->getIdentifierKey(), $id)->first();
    }

    /**
     * Runs a simple 'where' query to find a model by its identifier. If no model is found, an exception is thrown.
     *
     * @param $id
     *
     * @return static
     * @throws NotFoundException
     */
    public static function findOrFail($id) : self
    {
        $model = self::find($id);

        if (!$model) {
            throw new NotFoundException();
        }
        return $model;
    }

    /**
     * Fetches all known entries from the database.
     *
     * @return Table
     */
    public static function all() : Table
    {
        try {
            return self::newQuery()->get();
        } catch (QueryException $e) {
            return Table::make();
        }
    }

    /**
     * Finds the first model that matches the given search filters, or creates (and saves) a new model with the given
     * data.
     *
     * @param array $searchData Data to use as the 'where' statement.
     * @param array $createData Data to use when creating a new model.
     *
     * @return static
     */
    public static function firstOrCreate(array $searchData = [], array $createData = []) : self
    {
        $model = static::firstOrNew($searchData, $createData);

        if ($model->fresh) { // model hasn't been saved in the database yet.
            $model->save();
        }

        return $model;
    }

    /**
     * Finds the first model that matches the given search filters, or creates (without saving) a new model with the
     * given data and search data.
     *
     * @param array $searchData Data to use as the 'where' statement.
     * @param array $createData Data to use when creating a new model.
     *
     * @return static
     */
    public static function firstOrNew(array $searchData = [], array $createData = []) : self
    {
        $searchData = table($searchData);

        $model = static::newQuery()
            ->where($searchData->map(fn($item, $key) => [$key, $item])->all())
            ->first();

        if (!is_null($model)) {
            return $model;
        }

        return static::make($searchData->merge($createData));
    }

    /**
     * Updates all models based on the search data. If no model could be found, it creates a singe new one instead.
     * This method is also known as 'upsert'.
     *
     * @param array $searchData The 'where' data to search for a model with.
     * @param array $updateData The data to update.
     *
     * @return bool|Model
     */
    public static function updateOrCreate(array $searchData = [], array $updateData = [])
    {
        $search = table($searchData)
            ->map(fn($value, $key) => [$key, $value])
            ->all();

        // check for the existence of the required models.
        $models = static::newQuery()
            ->where($search)
            ->count();

        if ($models === 0) {
            // create a new model
            return static::create(table($searchData)->merge($updateData));
        }

        // Update the list of models and return true / false
        return !!static::newQuery()
            ->where($search)
            ->update($updateData);
    }

    /**
     * Attempts to update the model in the database using the dirty fields.
     *
     * @param array $moreChanges Any more changes to be made.
     *
     * @return bool Whether the update was successful.
     */
    public function update(array $moreChanges = []) : bool
    {
        // Update the fields before processing
        foreach ($moreChanges as $name => $value) {
            $this->__set($name, $value);
        }

        return $this->save();
    }

    /**
     * Attempts to update all changed fields.
     */
    public function save() : bool
    {
        if (!$this->dirty()) {
            // No changes have been detected and so updating the database would be pointless.
            return true;
        }

        // find all changed items.
        $changes = $this->getAllChanges();

        // Create a new query
        $query = $this->newQuery($this);

        // detect whether we need to run UPDATE or INSERT
        if (is_null($this->getIdentifier()) || $this->fresh) {
            // We haven't set an identifier, which likely means the model is 'fresh'
            
            if ($this->hasDates) {
                // Update the created_at field
                $this->created_at = $this->updated_at;

                // and add it to the change list.
                $changes['created_at'] = $this->created_at;
            }

            // Run the insert query
            $result = $query->insert($changes);

            if (is_numeric($result)) {
                // we've received the auto-increment ID.
                $this->setIdentifier($result);

                $this->fill($this->allAttributes());
            }

            if ($result) {
                $this->fresh = false;
            }

        } else {
            // We have set an identifier, which likely means the model already exists.

            // Run the update query
            $result = $query->where($this->getIdentifierKey(), $this->getIdentifier())
                ->limit()
                ->update($changes);
        }

        return !!$result;
    }

    /**
     * Attempts to remove the model from the database.
     *
     * @param bool $force Whether to permanently delete models that would otherwise be soft-deleted.
     *
     * @return bool whether it was successfully deleted.
     */
    public function delete(bool $force = false) : bool
    {
        $query = $this->newQuery($this);

        return $query->where($this->getIdentifierKey(), $this->getIdentifier())
            ->limit()
            ->delete($force);
    }

    /**
     * Attempts to delete models by the given primary id.
     *
     * @param int|int[]|BaseTable $ids
     *
     * @return bool
     */
    public static function destroy($ids) : bool
    {
        $instance = new static;
        $ids = table($ids)->all();

        return !!$instance::newQuery()
            ->whereIn($instance->getIdentifierKey(), $ids)
            ->delete();
    }

    public function forceDelete() : bool
    {
        return $this->delete(true);
    }

    /**
     * Restores a deleted model.
     * 
     * @return bool whether it was successfully restored.
     */
    public function restore() : bool
    {
        $this->deleted_at = null;
        
        return $this->save();
    }

    /**
     * Returns whether this model has been soft-deleted.
     *
     * @return bool
     */
    public function deleted() : bool
    {
        if ($this->softDeletes()) {
            return $this->deleted_at !== null;
        }

        return false;
    }

    /**
     * Attempts to delete all records and reset the Auto-Increment ID.
     *
     * If there are any foreign keys pointing to any of these models, an exception is quite likely.
     *
     * @return bool
     * @throws DatabaseException
     */
    public function truncate() : bool
    {
        return Schema::truncate($this->getTable())
            ->execute();
    }

    /**
     * Re-loads the model from the database.
     *
     * @return bool
     */
    public function refresh() : bool
    {
        $result = self::newQuery($this)
            ->where($this->getIdentifierKey(), $this->getIdentifier())
            ->first();

        if (!($result instanceof $this)) {
            return false;
        }

        $this->fill($result->allAttributes());

        // clear relation array
        $this->relations = [];

        return true;
    }

    /**
     * Creates a new instance of this model and then creates a new Query for it.
     *
     * @param Model|null $model The optional modal instance
     *
     * @return Query
     */
    public static function newQuery(self $model = null) : Query
    {
        $model = $model ?? new static;

        return new Query($model);
    }

}