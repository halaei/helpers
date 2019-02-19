<?php

namespace HalaeiTests;

use Carbon\Carbon;
use Halaei\Helpers\Eloquent\Cacheable;
use Halaei\Helpers\Eloquent\CacheableTrait;
use Halaei\Helpers\Eloquent\EloquentCache;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Mockery\MockInterface;

class EloquentCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MockInterface|CachedModel
     */
    private $model;

    /**
     * @var MockInterface|CacheableModel
     */
    private $cacheable;

    /**
     * @var MockInterface|Repository
     */
    private $cache;

    public function setUp()
    {
        parent::setUp();
        $this->model = Mockery::mock(CachedModel::class);
        $this->cacheable = Mockery::mock(CacheableModel::class);
        $this->cache = Mockery::mock(Repository::class);
        Carbon::setTestNow(Carbon::now());
    }

    protected function tearDown()
    {
        Mockery::close();
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function test_find_from_cache()
    {
        $model = new CacheableModel();
        $repository = new EloquentCache($model, $this->cache);
        $this->cache->shouldReceive('get')->once()->with('elq-ch:cacheable_models:1')->andReturn(['id' => 1, 'column' => 'value']);
        $result = $repository->find(1);
        $this->assertEquals(['id' => 1, 'column' => 'value'], $result->toArray());
        $this->assertTrue($result->isCached());
    }

    public function test_find_from_db()
    {
        $repository = new EloquentCache($this->cacheable, $this->cache, 'cacheable_models');
        $this->cache->shouldReceive('get')->once()->with('elq-ch:cacheable_models:2')->andReturn(null);
        $this->cacheable->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->cacheable->shouldReceive('where')->once()->with('id', '=', 2)->andReturnSelf();
        $this->cacheable->shouldReceive('firstOrFail')->once()->andReturnSelf();
        $this->cacheable->shouldReceive('getOriginal')->once()->andReturn(['id' => 2, 'column' => 'value']);
        $this->cache->shouldReceive('put')->once()
            ->with('elq-ch:cacheable_models:2', ['id' => 2, 'column' => 'value'], Mockery::on(function (Carbon $time) {
                return Carbon::now()->timestamp + 600 === $time->timestamp;
            }));
        $result = $repository->find(2);
        $this->assertSame($this->cacheable, $result);
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function test_fail_to_find()
    {
        $repository = new EloquentCache($this->cacheable, $this->cache, 'cacheable_models');
        $this->cache->shouldReceive('get')->once()->with('elq-ch:cacheable_models:2')->andReturn(null);
        $this->cacheable->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->cacheable->shouldReceive('where')->once()->with('id', '=', 2)->andReturnSelf();
        $this->cacheable->shouldReceive('firstOrFail')->andThrow(ModelNotFoundException::class);
        $repository->find(2);
    }

    public function test_find_by_secondary_from_cache()
    {
        $repository = new EloquentCache(new CachedModel(), $this->cache, 'cached_models');
        $this->cache->shouldReceive('get')->once()->with('elq-ch:cached_models:s:column1,column2:value1,value2')->andReturn(3);
        $this->cache->shouldReceive('get')->once()->with('elq-ch:cached_models:3')->andReturn(['id' => 3, 'column1' => 'value1', 'column2' => 'value2']);
        $repository->findBySecondaryKey(['column1' => 'value1', 'column2' => 'value2']);
    }

    public function test_find_by_secondary_id_from_cache_model_from_db()
    {
        $repository = new EloquentCache($this->model, $this->cache, 'cached_models');
        $this->cache->shouldReceive('get')->once()->with('elq-ch:cached_models:s:column1,column2:value1,value2')->andReturn(3);
        $this->cache->shouldReceive('get')->once()->with('elq-ch:cached_models:3')->andReturn(null);

        $this->model->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with('id', '=', 3)->andReturnSelf();
        $this->model->shouldReceive('firstOrFail')->once()->andReturnSelf();
        $this->model->shouldReceive('getOriginal')->once()->andReturn(['id' => 3, 'column' => 'value']);
        $this->cache->shouldReceive('put')->once()
            ->with('elq-ch:cached_models:3', ['id' => 3, 'column' => 'value'], Mockery::on(function (Carbon $time) {
                return Carbon::now()->timestamp + 600 === $time->timestamp;
            }));

        $this->assertSame($this->model, $repository->findBySecondaryKey(['column1' => 'value1', 'column2' => 'value2']));
    }

    public function test_find_by_secondary_from_db()
    {
        $repository = new EloquentCache($this->model, $this->cache, 'cached_models');
        $this->cache->shouldReceive('get')->once()->with('elq-ch:cached_models:s:column1,column2:value1,value2')->andReturn(null);

        $this->model->shouldReceive('newQuery')->once()->andReturnSelf();
        $this->model->shouldReceive('where')->once()->with(['column1' => 'value1', 'column2' => 'value2'])->andReturnSelf();
        $this->model->shouldReceive('firstOrFail')->once()->andReturnSelf();
        $this->model->shouldReceive('getKey')->twice()->andReturn(3);
        $this->model->shouldReceive('getOriginal')->once()->andReturn(['id' => 3, 'column1' => 'value1', 'column2' => 'value2']);
        $this->cache->shouldReceive('putMany')->once()->with([
            'elq-ch:cached_models:3' => ['id' => 3, 'column1' => 'value1', 'column2' => 'value2'],
            'elq-ch:cached_models:s:column1,column2:value1,value2' => 3,
        ], Mockery::on(function (Carbon $time) {
            return Carbon::now()->timestamp + 600 === $time->timestamp;
        }));

        $this->assertSame($this->model, $repository->findBySecondaryKey(['column1' => 'value1', 'column2' => 'value2']));
    }

    public function test_update_non_dirty_model()
    {
        $repository = new EloquentCache($this->model, $this->cache, 'cached_models');
        $model = Mockery::mock(CachedModel::class);
        $model->shouldReceive('isDirty')->once()->andReturn(false);
        $repository->update($model);
    }

    public function test_update_dirty_model()
    {
        $repository = new EloquentCache($this->model, $this->cache, 'cached_models');
        $model = Mockery::mock(CachedModel::class);
        $model->shouldReceive('isDirty')->once()->andReturn(true);
        $model->shouldReceive('getKey')->twice()->andReturn(3);
        $this->cache->shouldReceive('put')->once()
            ->with('elq-ch:cached_models:3', 'invalid', Mockery::on(function (Carbon $time) {
            return Carbon::now()->timestamp + 600 === $time->timestamp;
        }));
        $model->shouldReceive('save')->once();
        $this->cache->shouldReceive('forget')->once()->with('elq-ch:cached_models:3');
        $repository->update($model);
    }

    public function test_delete()
    {
        $repository = new EloquentCache($this->model, $this->cache, 'cached_models');
        $model = Mockery::mock(CachedModel::class);
        $model->shouldReceive('getKey')->once()->andReturn(3);
        $this->cache->shouldReceive('put')->once()
            ->with('elq-ch:cached_models:3', 'invalid', Mockery::on(function (Carbon $time) {
                return Carbon::now()->timestamp + 600 === $time->timestamp;
            }));
        $this->model->shouldReceive('newQuery')->andReturnSelf();
        $this->model->shouldReceive('getKeyName')->once()->andReturn('id');
        $this->model->shouldReceive('where')->once()->with('id', '=', 3)->andReturnSelf();
        $this->model->shouldReceive('delete')->once();
        $repository->delete($model);
    }

    public function test_invalidate()
    {
        $repository = new EloquentCache($this->model, $this->cache, 'cached_models');
        $this->cache->shouldReceive('put')->once()
            ->with('elq-ch:cached_models:4', 'invalid', Mockery::on(function (Carbon $time) {
                return Carbon::now()->timestamp + 600 === $time->timestamp;
            }));
        $repository->invalidateCache(4);
    }

    public function test_forget()
    {
        $repository = new EloquentCache($this->model, $this->cache, 'cached_models');
        $this->cache->shouldReceive('forget')->once()->with('elq-ch:cached_models:4');
        $repository->forget(4);
    }
}

class CachedModel extends Model
{
    protected $guarded = ['id'];
}
class CacheableModel extends Model implements Cacheable
{
    use CacheableTrait;

    protected $guarded = ['id'];
}
