<?php

namespace Halaei\Helpers\Objects;

interface Rawable
{
    /**
     * Get the raw data of the instance.
     *
     * @return mixed
     */
    public function toRaw();
}
