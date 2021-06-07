<?php

namespace Halaei\Helpers\Eloquent;

use Carbon\Carbon;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentCache
{
    /**
     * The bot eloquent model instance.
     *
     * @var Model
     */
    protected $model;

    /**
     * The cache repository instance.
     *
     * @var Repository
     */
    protected $cache;

    /**
     * Minutes to cache bot models.
     *
     * @var int
     */
    protected $minutesToCache;

    /**
     * The key used for caching the models.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * EloquentCache constructor.
     *
     * @param Model $model
     * @param Repository|null $cache
     * @param string|null $cacheKey
     * @param int|float $minutesToCache
     */
    public function __construct(Model $model, Repository $cache = null, $cacheKey = null, $minutesToCache = 10)
    {
        $this->model = $model;
        $this->cache = $cache ? : app(Repository::class);
        $this->cacheKey = $cacheKey ? : $model->getTable();
        $this->minutesToCache = $minutesToCache;
    }

    /**
     * Get the name of the key that holds the cached model with the given id.
     *
     * @param string $id
     *
     * @return string
     */
    protected function getPrimaryCacheKey($id)
    {
        return 'elq-ch:'.$this->cacheKey.':'.$id;
    }

    /**
     * Get the name of the key that holds the primary key of the associated model.
     *
     * @param array $secondary
     *
     * @return string
     */
    protected function getSecondaryCacheKey(array $secondary)
    {
        $keys = join(',', array_keys($secondary));
        $values = join(',', array_values($secondary));
        return 'elq-ch:'.$this->cacheKey.":s:$keys:$values";
    }

    /**
     * Find a model by primary key.
     *
     * @param mixed $id
     * @param bool $useCache
     *
     * @throws ModelNotFoundException
     *
     * @return Model
     */
    public function find($id, $useCache = true)
    {
        $key = $this->getPrimaryCacheKey($id);
        $cached = null;
        if ($useCache) {
            $cached = $this->cache->get($key);
            if (! is_null($cached) && ! is_array($cached)) {
                // The cache is invalid.
                $useCache = false;
            }
        }

        if ($useCache && is_array($cached)) {
            $model = $this->model->newFromBuilder($cached);
            if ($model instanceof Cacheable) {
                $model->markAsCached();
            }
            return $model;
        }

        $model = $this->model->newQuery()->where('id', '=', $id)->firstOrFail();

        if ($useCache) {
            $this->cache->put($key, $model->getRawOriginal(), $this->cacheDuration());
        }

        return $model;
    }

    /**
     * Find a model by secondary key.
     *
     * @param array $secondary
     * @param bool $useCache
     *
     * @throws ModelNotFoundException
     *
     * @return Model
     */
    public function findBySecondaryKey(array $secondary, $useCache = true)
    {
        if (! $useCache) {
            return $this->model->newQuery()->where($secondary)->firstOrFail();
        }
        $secondaryKey = $this->getSecondaryCacheKey($secondary);
        $id = $this->cache->get($secondaryKey);

        if (is_null($id)) {
            $model = $this->model->newQuery()->where($secondary)->firstOrFail();
            $this->cache->putMany([
                $secondaryKey => $model->getKey(),
                $this->getPrimaryCacheKey($model->getKey()) => $model->getRawOriginal(),
            ], $this->cacheDuration());
        } else {
            $model = $this->find($id);
        }

        return $model;
    }

    /**
     * Update a model.
     *
     * @param Model $model
     */
    public function update(Model $model)
    {
        if (! $model->isDirty()) {
            return;
        }
        $this->invalidateCache($model);
        $model->save();
        $this->cache->forget($this->getPrimaryCacheKey($model->getKey()));
    }

    /**
     * Delete a model.
     *
     * @param $id
     */
    public function delete($id)
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }
        $this->invalidateCache($id);
        $this->model->newQuery()->where($this->model->getKeyName(), '=', $id)->delete();
    }

    /**
     * Force others to not use the cached data by writing garbage in the cache.
     *
     * @param mixed|Model $id
     * @param int|float $minutes
     */
    public function invalidateCache($id, $minutes = null)
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }
        $this->cache->put($this->getPrimaryCacheKey($id), 'invalid', $this->cacheDuration($minutes));
    }

    /**
     * Remove a model from the cache.
     *
     * @param mixed|Model $id
     */
    public function forget($id)
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }
        $this->cache->forget($this->getPrimaryCacheKey($id));
    }

    protected function cacheDuration($minutes = null)
    {
        $minutes = $minutes ?? $this->minutesToCache;
        return Carbon::now()->addSeconds((int) 60 * $minutes);
    }
}
