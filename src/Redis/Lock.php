<?php

namespace Halaei\Helpers\Redis;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Redis\RedisManager;
use Predis\ClientInterface;

class Lock
{
    /**
     * @var ClientInterface
     */
    protected $redis;

    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public static function instance($connection = null)
    {
        return new static(app(RedisManager::class)->connection($connection)->client());
    }

    /**
     * Wait at most $tr seconds for a lock with the given name and auto-release timer $tr.
     *
     * @param  string    $name  The name of the lock
     * @param  int|float $tr    Auto-release time in seconds
     * @return bool             true on success
     */
    public function lock($name, $tr = 2)
    {
        $LUA = <<<'LUA'
if (redis.call('exists', KEYS[1]) == 0) then
    if (not redis.call('rpoplpush', KEYS[2], KEYS[1])) then
        redis.call('lpush', KEYS[1], '1')
    end
    redis.call('pexpire', KEYS[1], ARGV[1])
    return true
end
return false
LUA;
        if ($this->redis->eval($LUA, 2, $name.'1', $name.'2', (int) ($tr * 1000))) {
            return true;
        }

        if ($this->redis->brpoplpush($name.'2', $name.'1', $tr)) {
            $this->redis->expire($name.'1', $tr);
            return true;
        }

        return false;
    }

    /**
     * Unlock/release the lock.
     *
     * @param  string $name The name of the lock
     */
    public function unlock($name)
    {
        $LUA = <<<'LUA'
if (redis.call('rpoplpush', KEYS[1], KEYS[2])) then
    redis.call('expire', KEYS[2], 5)
end
LUA;
        $this->redis->eval($LUA, 2, $name.'1', $name.'2');
    }

    /**
     * Run a callback after acquiring a lock, or fail if lock can't be acquired.
     *
     * @param  string $name         The name of the lock.
     * @param  callable $callback   The callback to run.
     * @param  int|float $tr        Auto-release time in seconds.
     * @param  int $retries         The number of retries for acquiring the lock.
     *
     * @return mixed                The callback return value
     *
     * @throws LockTimeoutException On failure to acquire the lock.
     */
    public function block($name, $callback, $tr = 2, $retries = 2)
    {
        for ($i = 0; $i < $retries; $i++) {
            if ($this->lock($name, $tr)) {
                try {
                    return $callback();
                } finally {
                    $this->unlock($name);
                }
            }
        }
        throw new LockTimeoutException();
    }
}
