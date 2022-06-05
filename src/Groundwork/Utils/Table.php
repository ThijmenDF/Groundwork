<?php

namespace Groundwork\Utils;

use Countable;
use Generator;
use IteratorAggregate;
use ArrayAccess;
use Groundwork\Traits\Serializable;
use JsonSerializable;

class Table implements IteratorAggregate, Countable, ArrayAccess, JsonSerializable
{
    use Serializable;

    /** The key for the Serializable trait */
    private string $serializeKey = 'values';

    /** Holds the actual objects */
    protected array $values = [];
    
    
    /**
     +--------------------------------------------+
     |                                            |
     |    Magic Methods                           |
     |                                            |
     |    Such as __get and interface methods     |
     |                                            |
     +--------------------------------------------+
     */

        /**
         * The constructor is private. Use `Table::make()` or `table()` to make a new table.
         */
        private function __construct(array $data = [])
        {
            $this->values = $data;
        }

        /**
         * Attempts to get an item
         * 
         * @param string|int $name
         * 
         * @return mixed The value or `null` if it doesn't exist
         */
        public function __get($name)
        {
            return $this->values[$name] ?? null;
        }

        /**
         * Attempts to set an item
         * 
         * @param string|int $name  The key to set the value at
         * @param mixed      $value The value to set
         */
        public function __set($name, $value) : void
        {
            $this->values[$name] = $value;
        }

        /**
         * Returns an ArrayIterator for the values, so that foreach loops can work.
         * 
         * @interface IteratorAggregate
         * 
         * @return Generator
         */
        public function getIterator() : Generator
        {
            yield from $this->values;
        }

        /**
         * Counts the table
         * 
         * @interface Countable
         * 
         * @return int
         */
        public function count() : int
        {
            return count($this->values);
        }

        /**
         * Returns whether the offset exists
         * 
         * @interface ArrayAccess
         * 
         * @param string|int $offset
         * 
         * @return bool
         */
        public function offsetExists($offset) : bool
        {
            return $this->has($offset);
        }

        /**
         * Gets the value of a specific offset
         * 
         * @interface ArrayAccess
         * 
         * @param string|int $offset
         * 
         * @return mixed
         */
        public function offsetGet($offset)
        {
            return $this->get($offset);
        }

        /**
         * Sets a specific offset's value
         * 
         * @interface ArrayAccess
         * 
         * @param string|int $offset
         * @param mixed      $value
         */
        public function offsetSet($offset, $value) : void
        {
            $this->__set($offset, $value);
        }

        /**
         * Clears a specific offset
         * 
         * @interface ArrayAccess
         * 
         * @param string|int $offset
         */
        public function offsetUnset($offset) : void
        {
            unset($this->values[$offset]);
        }



    
    /**
     +--------------------------------------------+
     |                                            |
     |    Common Methods                          |
     |                                            |
     |    That can't be otherwise categorized     |
     |                                            |
     +--------------------------------------------+
     */

        /**
         * Creates a new instance of the table object.
         * 
         * @param mixed $items
         * 
         * @return static
         */
        public static function make($items = []) : self
        {
            if ($items instanceof self) {
                // No need to make another Table if it's a Table already.
                return $items;
            }
            if (!is_array($items)) {
                $items = [$items];
            }

            return new static($items);
        }

        /**
         * Creates a new clone of itself
         * 
         * @return static
         */
        public function clone() : self
        {
            return static::make($this->values);
        }

        /**
         * Creates a new table from the values returned by the `$callback`, which is run a specific `$amount` of times.
         * 
         * Alternatively, a non-callable parameter can be passed which serves as a direct copy.
         * 
         * @param int   $amount
         * @param mixed $callback Which is given the iteration count as its first and only parameter, or just a plain value
         * 
         * @return static
         */
        public static function repeat(int $amount, $callback) : self
        {
            if (is_callable($callback)) {
                // Hold this for me, would you?
                $items = [];
                // Run a loop a given amount of times, executing the callback with the iteration counter as its parameter.
                for ($i = 0; $i < $amount; $i++) {
                    $items[] = $callback($i);
                }

                return static::make($items);
            }

            return static::make(array_fill(0, $amount, $callback));

        }

        /**
         * Returns the full list of array items
         * 
         * @return array
         */
        public function all() : array
        {
            return $this->values;
        }

        /**
         * Runs a callable function on each item
         * 
         * @param callable $callback Called with `(&$value, $key)`. 
         *                           The value may be changed within the method.
         * 
         * @return static For chaining
         */
        public function each(callable $callback) : self
        {
            foreach ($this->values as $key => &$value) {
                $result = $callback($value, $key);

                if ($result === false) {
                    break;
                }
            }

            return $this;
        }

        /**
         * Gets the given key. If it does not exist, returns null or the second default parameter.
         * 
         * @param string|int $key
         * @param mixed      $default
         * 
         * @return mixed
         */
        public function get($key, $default = null)
        {
            return $this->__get($key) ?? $default;
        }

        /**
         * Sets the given key.
         * 
         * @param string|int $key
         * @param mixed      $value
         */
        public function set($key, $value)
        {
            $this->__set($key, $value);
        }

        /**
         * Returns whether the table is empty.
         * 
         * @return bool
         */
        public function isEmpty() : bool
        {
            return $this->count() === 0;
        }

        /**
         * Returns whether the table is not empty.
         * 
         * @return bool
         */
        public function isNotEmpty() : bool
        {
            return !$this->isEmpty();
        }


    
    /**
     +--------------------------------------------+
     |                                            |
     |    Extraction Methods                      |
     |                                            |
     |    Taking data without changing the table  |
     |                                            |
     +--------------------------------------------+
     */

        /**
         * Extracts the keys of the array
         * 
         * @return self
         */
        public function keys() : self
        {
            return new self(array_keys($this->values));
        }

        /**
         * Extracts the values of the array
         * 
         * @return static
         */
        public function values() : self
        {
            return new static(array_values($this->values));
        }

        /**
         * Filters the array and returns a new one with items that passed the truth test.
         * 
         * @param callable|null $callback The truth test. Return some form of true to pass, a form of false to reject
         * 
         * @return static
         */
        public function filter(callable $callback = null) : self
        {
            return new static(
                array_filter($this->values, $this->truthTest($callback), ARRAY_FILTER_USE_BOTH)
            );
        }

        /**
         * Filters the array and returns a new one with items that passed the truth test.
         * 
         * @param callable|null $callback The truth test. Return some form of false to pass, a form of true to reject
         * 
         * @return static
         */
        public function reject(callable $callback = null) : self
        {
            return new static(
                array_filter($this->values, $this->truthTest($callback, true), ARRAY_FILTER_USE_BOTH)
            );
        }

        /**
         * Extracts the first item that passes the truth test, or just the first item if no callback is given.
         * 
         * @param callable|null $callback
         * 
         * @return mixed
         */
        public function first(callable $callback = null)
        {
            $test = $this->truthTest($callback);

            foreach ($this->values as $key => $value) {
                if (!$callback) {
                    return $value;
                }

                if ($test($value, $key)) {
                    return $value;
                }
            }

            return null;
        }

        /**
         * Extracts the last element that passes the truth test, or just the last item if no callback is given.
         * 
         * @param callable|null $callback
         * 
         * @return mixed
         */
        public function last(callable $callback = null)
        {
            $reversed = array_reverse($this->values);

            $test = $this->truthTest($callback);

            foreach ($reversed as $key => $value) {
                if (!$callback) {
                    return $value;
                }

                if ($test($value, $key)) {
                    return $value;
                }
            }

            return null;
        }
        
        /**
         * Tests each item in `$items` for if they occur in this table. Returns all items that do not exist in `$items`
         * 
         * @param array|Table $items
         * 
         * @return static
         */
        public function diff($items) : self
        {
            $items = static::make($items);

            $differences = array_diff($this->values, $items->all());

            return new static($differences);
        }

        /**
         * Test each item in `$items` for if their keys occur in this table. Returns all items which keys do not exist in `$items`.
         * 
         * @param array|Table $items
         * 
         * @return static
         */
        public function diffKeys($items) : self
        {
            $items = static::make($items);

            $differences = array_diff_key($this->values, $items->all());

            return new static($differences);
        }

        /**
         * Returns a table that do not contain the given keys
         * 
         * @see only For the inverse
         * 
         * @param string[]|Table|string $items
         * 
         * @return static
         */
        public function except($items) : self
        {
            $items = static::make($items);

            return $this->filter(fn($value, $key) => !$items->contains($key));
        }

        /**
         * Returns a table that only contains the given keys
         * 
         * @see except For the inverse
         * 
         * @param string[]|Table|string $items
         * 
         * @return static
         */
        public function only($items) : self
        {
            $items = static::make($items);

            return $this->filter(fn($value, $key) => $items->contains($key));
        }

        /**
         * Takes a specific key from a multi-dimensional array and returning it as a single-dimensional array
         * 
         * @param string|int $key
         * 
         * @return self
         */
        public function pluck($key) : self
        {
            $filtered = $this->filter(fn($item) => is_iterable($item) && isset($item[$key]))
                ->map(fn($item) => $item[$key]);

            return self::make($filtered);
        }

        /**
         * Return a random item from the table.
         * 
         * If `$amount` is larger than 1, it will return a table with that many random items.
         * 
         * @param int $amount
         * 
         * @return Table|mixed
         */
        public function random(int $amount = 1)
        {
            $keys = array_rand($this->values, $amount);

            if (is_array($keys)) {
                // Look up all the keys
                $list = [];
                foreach ($keys as $key) {
                    $list[] = $this->get($key);
                }
                return static::make($list);
            }
            
            // Simply return the only and first item.
            return $this->get($keys);
        }

        /**
         * Return a new table containing only the values that came after the $amount's index.
         * 
         * @param int $amount
         * 
         * @return static
         */
        public function skip(int $amount = 1) : self
        {
            return $this->slice($amount);
        }

        /**
         * Return a new table containing only the values after the first index that passes the given truth test.
         * 
         * @param callable $test
         * 
         * @return static
         */
        public function skipUntil(callable $test) : self
        {
            // Make a new truth test
            $test = $this->truthTest($test);

            // The index to run the skip to
            $index = 0;

            // Loop through the values until the truth test passes
            foreach ($this->values as $key => $value) {
                if ($test($value, $key)) {
                    break;
                }
                $index++;
            }

            return $this->slice($index);
        }

        /**
         * Returns a sliced section the table, starting from index `$from` and returning everything afterwards.
         * 
         * You may also pass a second `$length` parameter to indicate the length of the sliced part
         */
        public function slice(int $from = 0, int $length = null) : self
        {
            return static::make(array_slice($this->values, $from, $length, true));
        }

        /**
         * Splits the table into chunks with `$length` size, returning it as a new table.
         * 
         * @param int $length The maximum size of each segment
         * 
         * @return static
         */
        public function split(int $length = 1) : self
        {
            $chunks = [[]];

            /** @var int The current length of the segment */
            $len = 0;
            /** @var int The current segment */
            $index = 0;

            foreach ($this->values as $key => $value) {
                // If the current segment length matches (or exceeds) the maximum length,
                // shift to the next segment and reset the values.
                if ($len >= $length) {
                    $len = 0;
                    $index++;
                    $chunks[$index] = [];
                }
                $len++;

                // Save the current item to the chunk
                if (is_int($key)) {
                    // Plain arrays should simply be added
                    $chunks[$index][] = $value;
                } else {
                    // Associate arrays should keep their keys
                    $chunks[$index][$key] = $value;
                }
            }

            return static::make($chunks);
        }

        /**
         * Returns `$length` amount of items from the beginning of the list
         * 
         * @param int $length
         * 
         * @return static
         */
        public function take(int $length = 1) : self
        {
            return $this->slice(0, $length);
        }

        /**
         * Returns a new table only containing unique values. The keys will not be affected.
         * 
         * @return static
         */
        public function unique() : self
        {
            return static::make(array_unique($this->values));
        }

        /**
         * Filters the table and collects all items where a given key and value match.
         * 
         * @param int|string $key
         * @param mixed      $value
         * 
         * @return static
         */
        public function where($key, $value) : self
        {
            return $this->filter(fn($item) => 
                is_iterable($item)      &&  // The item is some form of array
                isset($item[$key])      &&  // the item has the required key
                $item[$key] === $value      // The value of that key matches
            );
        }

        /**
         * Counts the occurrences of values. An optional callback may be passed to subsidize the keys.
         *
         * @param callable|null $callback The returned value from this counts as the key.
         *
         * @return static
         */
        public function countBy(callable $callback = null) : self
        {
            $counts = [];

            foreach ($this->values as $value) {
                // Replace the key with whatever comes out of the callback.
                if (is_callable($callback)) {
                    $value = $callback($value);
                }

                // If this key hasn't been seen before, make a new entry for it.
                if (!isset($counts[$value])) {
                    $counts[$value] = 0;
                }

                // *click*
                $counts[$value]++;
            }

            return static::make($counts);
        }

        /**
         * Returns all duplicate items from the table (preserving keys)
         * 
         * @return static
         */
        public function duplicates() : self
        {
            $hadValues  = static::make();
            $duplicates = static::make();

            // Loop through every item
            foreach ($this->values as $key => $value) {

                // If the value has already been seen, add that to the duplicates
                if ($hadValues->contains($value)) {
                    $duplicates->set($key, $value);
                }

                // Save the item as we've just seen it.
                $hadValues->push($value);
            }

            return $duplicates;
        }

    /**
    +--------------------------------------------+
    |                                            |
    |    Alteration Methods                      |
    |                                            |
    |    Changing the table's values             |
    |                                            |
    +--------------------------------------------+
    */

        /**
         * Adds items to the back of the list.
         *
         * @param mixed $value
         *
         * @return static
         */
        public function push($value) : self
        {
            if($value instanceof self) {
                $items = $value->values()->all();
            } else {
                $items = [$value];
            }

            array_push($this->values, ...$items);

            return $this;
        }

        /**
         * Pops off the last item of the table
         * 
         * @return mixed
         */
        public function pop()
        {
            return array_pop($this->values);
        }

        /**
         * Adds items to the front of the list.
         * 
         * @return static
         */
        public function prepend($value) : self
        {
            // Reverse the array
            $reversed = array_reverse($this->values);

            // Push the item to the back
            $reversed[] = $value;

            // Reverse it again
            $this->values = array_reverse($reversed);

            return $this;
        }

        /**
         * Takes the item by its key and remove it from the table
         * 
         * @return mixed
         */
        public function pull($key)
        {
            // Check if the table has the key in the first place.
            if (!$this->has($key)) {
                return null;
            }

            $offset = 0;

            // Search the array and find the index
            foreach ($this->values as $index => $value) {
                if ($key === $index) {
                    // Key was found. Stop looping
                    break;
                }
                $offset++;
            }

            $extracted = array_splice($this->values, $offset, 1);

            return $extracted[$key] ?? $extracted ?? null;
        }

        /**
         * Clears the contents of the table entirely.
         *
         * @return static
         */
        public function clear() : self
        {
            $this->values = [];

            return $this;
        }

        /**
         * Combine the table with an array of data. The table serves as keys while the `$items` serves as values
         * 
         * @param array|Table $items
         * 
         * @return static
         */
        public function combine($items) : self
        {
            if ($items instanceof self) {
                $items = $items->values()->all();
            }

            $keys = $this->keys()->all();

            $this->values = array_combine($keys, $items);

            return $this;
        }

        /**
         * Add the values of `$items` to the table, ignoring keys.
         * 
         * @param array|Table $items
         * 
         * @return static
         */
        public function concat($items) : self
        {
            $items = static::make($items)->values();

            $this->push($items);

            return $this;
        }

        /**
         * Splices the table, returning a specific section with optional length, and adding
         * in the optional replacements at the same point
         *
         * @param int      $start
         * @param int|null $length
         * @param mixed    $replacement
         *
         * @return static The cut-off section, leaving the rest back in the original table
         */
        public function splice(int $start, int $length = null, $replacement = []) : self
        {
            $cutoff = array_splice($this->values, $start, $length, $replacement);

            return static::make($cutoff);
        }

        /**
         * Merges another table or array into the current table.
         *
         * Duplicate keys are replaced with the new ones, while keyless (numeric) lists are simply appended.
         * 
         * @param array|Table $items
         * 
         * @return static
         */
        public function merge($items) : self
        {
            $items = table($items);

            $this->values = array_merge($this->values, $items->all());

            return $this;
        }

        /**
         * Merge another table or array into the current table. Instead of straight out replacing the keys, if matching
         * keys are found they are merged recursively.
         * 
         * @param array|Table $items
         * 
         * @return static
         */
        public function mergeRecursive($items) : self
        {
            $this->values = array_merge_recursive($this->values, $items);

            return $this;
        }

        /**
         * Takes the values and keys from $items and replaces its own values with them. Unlike merge, this method also
         * replaces numeric keys.
         * 
         * @param array|Table $items
         * 
         * @return static
         */
        public function replace($items) : self
        {
            $this->values = array_replace($this->values, static::make($items)->all());

            return $this;
        }
        
        /**
         * Takes the values and keys from $items and replaces its own values with them. Unlike merge, this method also
         * replaces numeric keys.
         * 
         * Should the same key be an array, it will replace the items in that array recursively as well.
         * 
         * @param array|Table $items
         * 
         * @return static
         */
        public function replaceRecursive($items) : self
        {
            $this->values = array_replace_recursive($this->values, static::make($items)->all());

            return $this;
        }

        /**
         * Fills in a table to match the count of `$amount`. If it does not match the length, it will be padded with
         * `$value`.
         * 
         * If `$amount` is negative, the `$value` will pad at the front of the table.
         * 
         * @param int   $amount
         * @param mixed $value
         * 
         * @return static
         */
        public function pad(int $amount, $value) : self
        {
            $this->values = array_pad($this->values, $amount, $value);

            return $this;
        }

        /**
         * Remove and return the first item from the table.
         * 
         * @return mixed
         */
        public function shift() 
        {
            return array_shift($this->values);
        }

        /**
         * Randomizes the order of the items in the table.
         * 
         * @return static
         */
        public function shuffle() : self
        {
            shuffle($this->values);

            return $this;
        }

        /**
         * Run a simple sorting algorithm on the table.
         * 
         * @return static
         */
        public function sort() : self
        {
            asort($this->values, SORT_NATURAL);

            return $this;
        }

        /**
         * Run a simple sorting algorithm on the table.
         * 
         * @return static
         */
        public function sortDesc() : self
        {
            arsort($this->values, SORT_NATURAL);

            return $this;
        }

        /**
         * Runs a sort on a multi-dimensional array.
         * 
         * @param int|string $key
         * 
         * @return static
         */
        public function sortBy($key) : self
        {
            uasort($this->values, function($a, $b) use($key) {
                if ($a[$key] > $b[$key]) { return -1; }
                if ($a[$key] < $b[$key]) { return  1; }
                else                     { return  0; }
            });

            return $this;
        }

        /**
         * Runs a sort on a multi-dimensional array.
         * 
         * @param int|string $key
         * 
         * @return static
         */
        public function sortByDesc($key) : self
        {
            uasort($this->values, function($a, $b) use($key) {
                if ($a[$key] > $b[$key]) { return  1; }
                if ($a[$key] < $b[$key]) { return -1; }
                else                     { return  0; }
            });

            return $this;
        }

        /**
         * Sorts the table by their keys.
         * 
         * @return static
         */
        public function sortKeys() : self
        {
            ksort($this->values);

            return $this;
        }

        /**
         * Sorts the table by their keys.
         * 
         * @return static
         */
        public function sortKeysDesc() : self
        {
            krsort($this->values);

            return $this;
        }

        /**
         * Run over each item in the table, executing `$callback` on each one. The result from `$callback` will
         * determine its new value.
         * 
         * @param callable $callback
         * 
         * @return static
         */
        public function transform(callable $callback) : self
        {
            foreach ($this->values as $key => &$value) {
                $value = $callback($value, $key);
            }

            return $this;
        }

        /**
         * Combines the table with another array. Unlike merge, if there's a duplicate item, the original version will
         * be kept.
         * 
         * @param array|Table $items
         * 
         * @return static
         */
        public function union($items) : self
        {
            if ($items instanceof self) {
                $items = $items->all();
            }

            $this->values = array_merge($items, $this->values);

            return $this;
        }

    /**
     +--------------------------------------------+
     |                                            |
     |    Transformation Methods                  |
     |                                            |
     |    Taking data through a transformation    |
     |    process                                 |
     |                                            |
     +--------------------------------------------+
     */


        /**
         * Flatten a two-dimensional list to a single, keyless dimension.
         * 
         * @return self
         */
        public function collapse() : self
        {
            return self::make($this->_flatten($this->values, 1));
        }

        /**
         * Flatten a multi-dimensional list to a single, keyless dimension.
         * 
         * @param int|float $depth How deep down into the dimensions the flattening should happen
         * 
         * @return self
         */
        public function flatten($depth = INF) : self
        {
            return self::make($this->_flatten($this->values, $depth));
        }

        /**
         * Flips the keys and values around.
         * 
         * @return self
         */
        public function flip() : self
        {
            return self::make(array_flip($this->values));
        }

        /**
         * Transform each item to something else based on the callback.
         *
         * @param callable $callback Run with `($value, $key)`
         *
         * @return static
         */
        public function map(callable $callback) : self
        {
            return static::make(array_map($callback, $this->values, array_keys($this->values)));
        }

        /**
         * Joins the table together with a glue string.
         *
         * If a second parameter is given, the list will join multidimensional arrays by that specific key.
         *
         * @param string|null $glue
         * @param int|string  $key
         *
         * @return string
         */
        public function implode(string $glue = null, $key = null) : string
        {
            // Without the 'glue' we want to use it as a simple array.
            if (is_null($key)) {

                // Filter our list and only take simple items (no arrays or object).
                $filtered = $this->reject(fn($item) => is_array($item) || is_object($item));

                // Merge the list with a simple implode call.
                return implode($glue, $filtered->all());
            }

            // key and glue are set. Filter the items and find everything that has the correct key.
            // Then collect the keys.
            $filtered = $this->filter(fn($item) =>
                    is_iterable($item) && // can be accessed as array
                    isset($item[$key]) && // has the given key
                    !is_null($item[$key]) // given key isn't null
                )
                ->map(fn($item) => $item[$key]);

            // Merge the list with a simple implode call.
            return implode($glue, $filtered->all());
        }

        /**
         * Flips the tables order around and returns it as a new table.
         * 
         * Preserves their original keys.
         * 
         * @return static
         */
        public function reverse() : self
        {
            return static::make(array_reverse($this->values, true));
        }


        /**
         * Merges an array onto the table. Indexes are maintained and their values are transformed into arrays (if they
         * aren't already).
         * 
         * New indexes are added as a plain array.
         * 
         * @param array|Table $items
         * 
         * @return self
         */
        public function zip($items) : self
        {
            if ($items instanceof self) {
                $items = $items->all();
            }

            // Make a new Table instance (not inheritable)
            $values = self::make($this->values);

            // Go over each item and make sure they're all arrays.
            $values->transform(fn($item) => is_iterable($item) ? $item : [$item]);

            // For each item in the given array...
            foreach ($items as $key => $item) {
                // If we already have something with that key...
                if ($values->has($key)) {

                    // save the value here, so we can change it later.
                    $value = $values->get($key);

                    // Add the given item to that array and save it.
                    $value[] = $item;

                    // Save it to our list.
                    $values->set($key, $value);
                } else {

                    // Set the item as a new array with the given item in it.
                    $values->set($key, [$item]);
                }
            }

            return $values;
        }

    /**
     +--------------------------------------------+
     |                                            |
     |    Testing Methods                         |
     |                                            |
     |    Running tests on (part of) the table    |
     |                                            |
     +--------------------------------------------+
     */

        /**
         * Returns whether a given key exists.
         * 
         * @param string $key
         * 
         * @return bool
         */
        public function has(string $key) : bool
        {
            return isset($this->values[$key]);
        }

        /**
         * Tests to see if the list contains anything matching the `$test` value, or matching a truth test.
         * 
         * @param mixed $test A value or a callable
         * 
         * @return bool
         */
        public function contains($test) : bool
        {
            // The test is callable, so make it a truth test.
            if (is_callable($test)) {
                $test = $this->truthTest($test);
                foreach ($this->values as $key => $value) {    
                    // Run a truth test
                    if ($test($value, $key)) {
                        return true;
                    }
                }

                return false;
            }

            // Run an equality check for test.
            if (array_search($test, $this->values, true) !== false) {
                return true;
            }

            return false;
        }

        /**
         * Runs a truth test on each item. The first item that fails the test breaks the loop. If they all pass, return
         * true.
         * 
         * @param callable $test
         * 
         * @return bool
         */
        public function every(callable $test) : bool
        {
            $test = $this->truthTest($test);

            foreach ($this->values as $key => $value) {
                if ($test($value, $key) === false) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Executes the callback once, passing it the current state of the table without being able to alter it.
         * 
         * @param callable $callback
         * 
         * @return static
         */
        public function tap(callable $callback) : self
        {
            $callback($this->clone());

            return $this;
        }

    /**
     +--------------------------------------------+
     |                                            |
     |    Private Methods                         |
     |                                            |
     |    Methods only used internally            |
     |                                            |
     +--------------------------------------------+
     */

        /**
         * Returns a callable function that serves as a truth test.
         *
         * @param callable|null $callback The manual method that can be called. If null, it will test the value as a
         *                                boolean
         * @param bool          $inverse  Whether to inverse the result of the callback
         * 
         * @return callable
         */
        private function truthTest(callable $callback = null, bool $inverse = false) : callable
        {
            return function($value, $key = null) use($callback, $inverse) {
                if (is_callable($callback)) {
                    if ($inverse) {
                        return !$callback($value, $key);
                    }
                    return $callback($value, $key);
                }
                return !!$value;
            };
        }

        /**
         * Internal method to handle the `flatten` method.
         * 
         * @param array     $list
         * @param int|float $depth
         * 
         * @return array
         */
        private function _flatten(array $list, $depth) : array
        {
            $values = [];

            foreach ($list as $item) {
                if ($item instanceof Table) { 
                    $item = $item->all();
                }

                if (is_array($item) && $depth > 0) {
                    array_push($values, ...$this->_flatten($item, $depth-1));
                }
                else {
                    $values[] = $item;
                }
            }

            return $values;
        }
}