<?php

namespace Halaei\Helpers\Eloquent;

interface Cacheable
{
    /**
     * Check if this model is loaded from cache.
     *
     * @return bool
     */
    public function isCached();

    /**
     * Mark the model as being loaded from cache.
     *
     * @return $this
     */
    public function markAsCached();

    /**
     * Make sure the model is loaded from DB. Reload data from DB if it is cached.
     *
     * Note: this method should not touch relations.
     *
     * @return $this
     */
    public function syncWithDB();
}
