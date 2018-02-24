<?php

namespace Halaei\Helpers\Objects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class DataObject implements Arrayable, Jsonable, Rawable
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
            if (array_key_exists($property, $this->data)) {
                $this->data[$property] = $this->makeRelated($property, $this->data[$property], $type);
            }
        }
    }

    /**
     * Convert the related property value into the given type.
     *
     * @param string $property
     * @param mixed $value
     * @param string|callable|array $type
     *
     * @return DataCollection|mixed
     */
    protected function makeRelated($property, $value, $type)
    {
        // Recursive array case:
        if (is_array($type) && count($type) == 1 && isset($type[0])) {
            return new DataCollection(array_map(function ($value) use ($property, $type) {
                return $this->makeRelated($property, $value, $type[0]);
            }, $value));
        }

        // Constructor case:
        if (is_string($type) && class_exists($type)) {
            return new $type($value);
        }

        // Callable case:
        if (is_callable($type)) {
            return $type($value);
        }

        throw new \LogicException("Cannot parse relation $property in ".static::class);
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
            $response = $this->__get(snake_case(substr($method, 3)));

            return $response;
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
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }
}
