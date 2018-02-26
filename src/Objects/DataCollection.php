<?php

namespace Halaei\Helpers\Objects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class DataCollection extends Collection implements Rawable
{
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
        }, $this->items);
    }

    /**
     * Fuse this collection with the given collection, by adding items that are new and fusing items with duplicate key.
     *
     * @param DataCollection $collection
     * @param callable|string $keyBy
     * @param array $except
     *
     * @return static
     */
    public function fuse(DataCollection $collection, $keyBy, array $except = [])
    {
        $dictionary = $this->keyBy($keyBy);

        foreach ($collection as $item) {
            $key = $this->valueRetriever($keyBy)($item);
            if ($dictionary->has($key)) {
                $dictionary[$key]->fuse($item, $except);
            } else {
                $dictionary[$key] = $item;
            }
        }

        return $dictionary->values();
    }
}
