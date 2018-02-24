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
}
