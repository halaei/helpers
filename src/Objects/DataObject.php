<?php

namespace Halaei\Helpers\Objects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

abstract class DataObject implements Arrayable
{
    /**
     * The original data.
     *
     * @var array
     */
    protected $data;

    /**
     * The list of relations.
     *
     * @var array
     */
    protected static $relations = [];

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->loadRelations();
        $this->validate();
    }

    /**
     * Get all of the items in the object.
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->all());
    }

    /**
     * Load the relations, by converting raw data into objects defined in static::$relations.
     */
    protected function loadRelations()
    {
        foreach (static::$relations as $key => $relation) {
            if (array_key_exists($key, $this->data)) {
                $this->data[$key] = $this->loadRelation($this->data[$key], $relation);
            }
        }
    }

    protected function loadRelation($value, $relation)
    {
        if (preg_match('/(.+)\[\]$/', $relation, $classes)) {
            return new Collection(array_map(function ($value) use ($classes) {
                return $this->loadRelation($value, $classes[1]);
            }, $value));
        } else {
            return new $relation($value);
        }
    }

    /**
     * Validate
     */
    protected function validate()
    {
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
    }
}
