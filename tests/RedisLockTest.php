<?php

namespace HalaeiTests;

use Halaei\Helpers\Redis\Lock;
use Predis\Client;

class RedisLockTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $redis;

    /**
     * @var Lock
     */
    private $lock;

    public function setUp()
    {
        parent::setUp();

        $this->redis = $this->getRedis();

        $this->redis->flushdb();

        $this->lock = new Lock($this->redis);
    }

    public function tearDown()
    {
        $this->redis->flushdb();

        parent::tearDown();
    }

    /**
     * @return Client
     */
    private function getRedis()
    {
        return new Client([
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 5,
            'timeout' => 10.0,
        ]);
    }

    public function test_lock_and_unlock_with_no_race()
    {
        $this->assertTrue($this->lock->lock('test', 10));

        $this->assertGreaterThan(1000, $this->redis->pttl('test1'));
        $this->assertLessThan(10001, $this->redis->pttl('test1'));

        $this->assertEquals(1, $this->redis->llen('test1'));

        $this->assertEquals(0, $this->redis->exists('test2'));

        $this->lock->unlock('test');

        $this->assertGreaterThan(1000, $this->redis->pttl('test2'));
        $this->assertLessThan(5001, $this->redis->pttl('test2'));

        $this->assertEquals(1, $this->redis->llen('test2'));

        $this->assertEquals(0, $this->redis->exists('test1'));
    }

    public function test_lock_twice_sequentially_and_unlock_when_it_is_late()
    {
        $this->assertTrue($this->lock->lock('test'));
        $this->assertFalse($this->lock->lock('test'));

        $this->assertEquals(0, $this->redis->exists('test1'));
        $this->assertEquals(0, $this->redis->exists('test2'));

        $this->lock->unlock('test');

        $this->assertEquals(0, $this->redis->exists('test1'));
        $this->assertEquals(0, $this->redis->exists('test2'));

        $this->lock->unlock('test');

        $this->assertEquals(0, $this->redis->exists('test1'));
        $this->assertEquals(0, $this->redis->exists('test2'));
    }

    public function test_under_stress()
    {
        $this->redis->disconnect();
        for ($i = 0; $i < 200; $i++) {

            if (! pcntl_fork()) {
                $this->child_under_stress();
            }
        }
        $this->parent_under_stress();
    }

    private function child_under_stress()
    {
        if ($this->lock->lock('test', 100)) {
            usleep(rand(1, 1000));
            if ($this->redis->setnx('check_the_lock_is_exclusive', 1)) {
                usleep(rand(1, 1000));
                $this->redis->lpush('ok', ['true']);
                $this->redis->del('check_the_lock_is_exclusive');
                $this->lock->unlock('test');
                die;
            }
        }

        $this->redis->lpush('ok', ['false']);
        die;
    }

    private function parent_under_stress()
    {
        for ($i = 0; $i < 200; $i++) {
            $this->assertEquals(['ok', 'true'], $this->redis->brpop(['ok'], 10));
        }
    }
}
