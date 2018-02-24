<?php

namespace Halaei\Helpers\Objects;

class Casting
{
    /**
     * Cast a value into the given type.
     *
     * @param mixed $value
     * @param string|callable|array $type
     * @param string|null $name
     *
     * @return DataCollection|mixed
     */
    public static function cast($value, $type, $name = null)
    {
        // Recursive array case:
        if (is_array($type) && count($type) == 1 && isset($type[0])) {
            return new DataCollection(array_map(function ($value) use ($type, $name) {
                return static::cast($value, $type[0], $name);
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

        throw new \LogicException("Cannot cast $name into the given type");
    }
}
