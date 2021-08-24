<?php

namespace Groundwork\Database;

use Groundwork\Database\Model;
use Groundwork\Utils\Table as BaseTable;

/**
 * A variant of the Table class that overwrites some methods.
 * 
 * It assumes all items in the table are Models.
 */
class Table extends BaseTable {

    /** @var Model[] $values */
    protected array $values = [];

    /**
     * Tests if a specific model is presented in the table.
     * 
     * @param mixed|Model $test
     */
    public function contains($test): bool
    {
        if ($test instanceof Model) {
            return array_search($test, $this->values, true) !== false;
        }

        return $this->filter(fn(Model $value) =>
                $value->getIdentifier() === $test
            )
            ->isNotEmpty();
    }

    /**
     * List all models that do not exist in the current list.
     * 
     * @param Table $items
     * 
     * @return self A table with all items that exist in this table, but not in the `$items` parameter
     */
    public function diff($items) : self
    {
        $differences = array_udiff(
            $this->values, 
            $items->all(), 
            fn(Model $a, Model $b) => $a->getIdentifier() === $b->getIdentifier() ? 0 : 1
        );

        return self::make($differences);
    }

    /**
     * Returns all models except for the primary keys or models listed in `$items`
     * 
     * @param array|BaseTable|Table $items
     * 
     * @return self
     */
    public function except($items): self
    {
        $items = $this->getIdentifiers($items);

        $filtered = $this->reject(fn(Model $model) => $items->contains($model->getIdentifier()));

        return self::make($filtered);
    }

    /**
     * Searches the array and attempt to find the models with the given ID, Model or list of models.
     *
     * @param int|Model|array|BaseTable $search
     *
     * @return Table|Model|null
     */
    public function find($search)
    {
        $items = $this->getIdentifiers($search);

        $filtered = $this->filter(fn(Model $model) => $items->contains($model->getIdentifier()));

        if ($items->count() === 1) {
            return $filtered->first();
        }

        return $filtered;
    }

    /**
     * Returns all identifiers for all models in the table
     * 
     * @return BaseTable
     */
    public function modelKeys() : BaseTable
    {
        return table(
            $this->map(fn(Model $model) => $model->getIdentifier())->all()
        );
    }

    /**
     * Returns all models with the given identifier keys
     * 
     * @param array $items
     * 
     * @return static
     */
    public function only($items) : self
    {
        return $this->find($items);
    }

    /**
     * Creates a new Query for the models stored in this table, with a whereIn constraint with the model's keys.
     * 
     * @return Query|null Null if the table is empty.
     */
    public function toQuery() : ?Query
    {
        /** @var Model|null The first model in this table */
        $model = $this->first();

        if (is_null($model)) {
            return null;
        }

        $query = new Query($model->getTable(), $model);

        return $query->whereIn('id', $this->modelKeys()->all());
    }

    /**
     * Returns a new table containing only models with unique ID's
     * 
     * @return Table
     */
    public function unique(): Table
    {
        $seenKeys = table();
        $uniqueKeys = self::make();

        foreach ($this->values as $key => $value) {
            $id = $value->getIdentifier();
            if (!$seenKeys->contains($id)) {
                $uniqueKeys[$key] = $value;
            }

            $seenKeys->push($id);
        }

        return $uniqueKeys;
    }

    /**
     * Takes a specific key from a multi-dimensional array and returning it as a single-dimensional array
     *
     * @param string|int $key
     *
     * @return self
     */
    public function pluck($key) : BaseTable
    {
        $filtered = $this->filter(fn(Model $item) => $item->has($key))
            ->map(fn(Model $item) => $item->get($key));

        return table($filtered);
    }

    /**
     * Attempts to save all models within the table.
     *
     * @return bool
     */
    public function save() : bool
    {
        return $this->every(fn(Model $item) => $item->save());
    }


    /**
     * Transforms the values into a BaseTable containing the Identifiers
     * 
     * @param int|array|BaseTable|Model $values
     * 
     * @return BaseTable
     */
    private function getIdentifiers($values) : BaseTable
    {
        return table($values)
            ->transform(fn($item) =>
                $item instanceof Model ? $item->getIdentifier() : (int) $item
            );
    }
}