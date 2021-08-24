<?php

namespace Groundwork\Traits;

/**
 * Adds methods that allow the object to be serialized (toJson and toArray).  
 * Requires a `serializeKey` to be present in the class, otherwise, the methods always return null.
 */
trait Serializable {

    /**
     * Returns a Json object of the serialized data
     * 
     * @return string
     */
    public function toJson() : string
    {
        $data = $this->getSerializingValue();

        return json_encode($data);
    }

    /**
     * Returns a plain array of the serialized data
     * 
     * @return array
     */
    public function toArray() : array
    {
        $data = $this->getSerializingValue();

        return array_values($data);
    }

    /**
     * Casts the value to a string.
     * 
     * @return string
     */
    public function __toString() : string
    {
        return $this->toJson();
    }

    /**
     * Gets the target data to serialize
     * 
     * @return null|array
     */
    private function getSerializingValue() : ?array
    {
        if (!isset($this->serializeKey)) { 
            return null;
        }
        
        return $this->{$this->serializeKey} ?? null;
    }

    /**
     * Converts the object to json.
     *
     * @resolves JsonSerializable
     */
    public function jsonSerialize() : ?array
    {
        return $this->getSerializingValue();
    }

}