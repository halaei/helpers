<?php

use Halaei\Helpers\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Redis\Database;
use Mockery as m;

class RedisCacheTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Database
     */
    private $redis;

    public function setUp()
    {
        parent::setUp();
        $this->setUpRedis();
    }

    public function tearDown()
    {
        parent::tearDown();
        m::close();
        $this->tearDownRedis();
    }

    public function setUpRedis()
    {
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('REDIS_PORT') ?: 6379;

        $this->redis = new Database([
            'cluster' => false,
            'default' => [
                'host' => $host,
                'port' => $port,
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);

        $this->redis->connection()->flushdb();
    }

    public function tearDownRedis()
    {
        if ($this->redis) {
            $this->redis->connection()->flushdb();
        }
    }

    public function testRedisCacheAddTwice()
    {
        $store = new RedisStore($this->redis);
        $repository = new Repository($store);
        $this->assertTrue($repository->add('k', 'v', 60));
        $this->assertFalse($repository->add('k', 'v', 60));
    }

    /**
     * Breaking change.
     */
    public function testRedisCacheAddFalse()
    {
        $store = new RedisStore($this->redis);
        $repository = new Repository($store);
        $repository->forever('k', false);
        $this->assertFalse($repository->add('k', 'v', 60));
    }

    /**
     * Breaking change.
     */
    public function testRedisCacheAddNull()
    {
        $store = new RedisStore($this->redis);
        $repository = new Repository($store);
        $repository->forever('k', null);
        $this->assertFalse($repository->add('k', 'v', 60));
    }
}
