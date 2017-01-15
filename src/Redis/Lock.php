<?php

namespace Halaei\Helpers\Redis;

use Illuminate\Redis\Database;

class Lock
{
    /**
     * @var Database
     */
    protected $redis;

    public function __construct(Database $redis)
    {
        $this->redis = $redis;
    }

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
        if ($this->redis->connection()->eval($LUA, 2, $name.'1', $name.'2', (int) ($tr * 1000))) {
            return true;
        }

        if ($this->redis->connection()->brpoplpush($name.'2', $name.'1', $tr)) {
            $this->redis->connection()->expire($name.'1', $tr);
            return true;
        }

        return false;
    }

    public function unlock($name, $tr = 2)
    {
        $LUA = <<<'LUA'
if (redis.call('rpoplpush', KEYS[1], KEYS[2])) then
    redis.call('pexpire', KEYS[2], ARGV[1])
end
LUA;
        $this->redis->connection()->eval($LUA, 2, $name.'1', $name.'2', (int) ($tr * 1000));
    }
}
