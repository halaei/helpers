<?php

namespace HalaeiTests;

use Halaei\Helpers\Redis\Lock;
use Illuminate\Redis\Database;

class RedisLockTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Database
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

        $this->redis->connection()->flushdb();

        $this->lock = new Lock($this->redis);
    }

    public function tearDown()
    {
        $this->redis->connection()->flushdb();

        parent::tearDown();
    }

    /**
     * @return Database
     */
    private function getRedis()
    {
        return new Database([
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);
    }

    public function test_lock_and_unlock_with_no_race()
    {
        $this->assertTrue($this->lock->lock('test', 10));

        $this->assertGreaterThan(1000, $this->redis->connection()->pttl('test1'));
        $this->assertLessThan(10001, $this->redis->connection()->pttl('test1'));

        $this->assertEquals(1, $this->redis->connection()->llen('test1'));

        $this->assertEquals(0, $this->redis->connection()->exists('test2'));

        $this->lock->unlock('test');

        $this->assertGreaterThan(1000, $this->redis->connection()->pttl('test2'));
        $this->assertLessThan(5001, $this->redis->connection()->pttl('test2'));

        $this->assertEquals(1, $this->redis->connection()->llen('test2'));

        $this->assertEquals(0, $this->redis->connection()->exists('test1'));
    }

    public function test_lock_twice_sequentially_and_unlock_when_it_is_late()
    {
        $this->assertTrue($this->lock->lock('test'));
        $this->assertFalse($this->lock->lock('test'));

        $this->assertEquals(0, $this->redis->connection()->exists('test1'));
        $this->assertEquals(0, $this->redis->connection()->exists('test2'));

        $this->lock->unlock('test');

        $this->assertEquals(0, $this->redis->connection()->exists('test1'));
        $this->assertEquals(0, $this->redis->connection()->exists('test2'));

        $this->lock->unlock('test');

        $this->assertEquals(0, $this->redis->connection()->exists('test1'));
        $this->assertEquals(0, $this->redis->connection()->exists('test2'));
    }

    public function test_under_stress()
    {
        $this->redis->connection()->disconnect();
        for ($i = 0; $i < 200; $i++) {

            if (! pcntl_fork()) {
                $this->child_under_stress();
            }
        }
        $this->parent_under_stress();
    }

    private function child_under_stress()
    {
        if ($this->lock->lock('test', 10)) {
            usleep(rand(1, 1000));
            if ($this->redis->connection()->setnx('check_the_lock_is_exclusive', 1)) {
                usleep(rand(1, 1000));
                $this->redis->connection()->lpush('ok', ['true']);
                $this->redis->connection()->del('check_the_lock_is_exclusive');
                $this->lock->unlock('test', 10);
                die;
            }
        }

        $this->redis->connection()->lpush('ok', ['false']);
        die;
    }

    private function parent_under_stress()
    {
        for ($i = 0; $i < 200; $i++) {
            $this->assertEquals(['ok', 'true'], $this->redis->connection()->brpop(['ok'], 10));
        }
    }
}
