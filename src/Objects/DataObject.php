<?php

namespace Halaei\Helpers\Objects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

abstract class DataObject implements Arrayable, Jsonable, Rawable
{
    /**
     * The original data.
     *
     * @var array
     */
    protected $data;

    /**
     * DataObject constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->mapRelations();
        $this->validate();
    }

    /*
     * Relations
     */

    /**
     * Define the relations that map properties into types.
     *
     * @return array
     */
    public static function relations()
    {
        return [
            // 'property_1' => SomeClass::class,
            // 'property_2' => [SomeClass::class],
            // 'property_3' => 'some_callable',
        ];
    }

    /**
     * Map properties to appropriate types based on static::relation().
     */
    protected function mapRelations()
    {
        foreach (static::relations() as $property => $type) {
            if (isset($this->data[$property])) {
                $this->data[$property] = Casting::cast($this->data[$property], $type, $property);
            }
        }
    }

    /*
     * Validations
     */
    /**
     * Validates the constructed object.
     */
    protected function validate()
    {
    }

    /*
     * Array/JSON Conversions
     */
    /**
     * Get all of the properties of the object.
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
     * Get the raw data of the instance.
     *
     * @return array
     */
    public function toRaw()
    {
        return array_map(function ($value) {
            if ($value instanceof Rawable) {
                return $value->toRaw();
            }
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->data);
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /*
     * Operations
     */

    /**
     * Fuse this object with a given object, by copying the properties.
     *
     * @param DataObject $object
     * @param array $except
     *
     * @return $this
     */
    public function fuse(DataObject $object, array $except = [])
    {
        foreach ($object->data as $prop => $val) {
            if (! in_array($prop, $except)) {
                $this->data[$prop] = $val;
            }
        }

        return $this;
    }

    /*
     * Magic Methods
     */

    /**
     * Dynamically call getters.
     *
     * @param  string $method
     * @param  array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $action = substr($method, 0, 3);

        if ($action === 'get') {
            return $this->__get(snake_case(substr($method, 3)));
        }

        if ($action === 'set' && count($parameters) == 1) {
            return $this->__set(snake_case(substr($method, 3)), $parameters[0]);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    /**
     * Dynamically access object properties.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (iseet($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * Dynamically set object properties.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Determine if a property exists on the object.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->__get($key));
    }

    /**
     * Dynamically unset object properties.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }
}
