<?php

namespace Halaei\Helpers\Eloquent;

trait CacheableTrait
{
    protected $isCached = false;

    /**
     * Check if this model is loaded from cache.
     *
     * @return bool
     */
    public function isCached()
    {
        return $this->isCached;
    }

    /**
     * Mark the model as being loaded from cache.
     *
     * @return $this
     */
    public function markAsCached()
    {
        $this->isCached = true;
        return $this;
    }

    /**
     * Make sure the model is loaded from DB. Reload data from DB if it is cached.
     *
     * @return $this
     */
    public function syncWithDB()
    {
        if ($this->isCached()) {
            $this->setRawAttributes(static::findOrFail($this->getKey())->attributes);
            $this->isCached = false;
        }
        return $this;
    }

}
