<?php

namespace Halaei\Helpers\Cache;

class RedisStore extends \Illuminate\Cache\RedisStore
{
    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])";

        $value = $this->serialize($value);

        return (bool) $this->connection()->eval($lua, 1, $this->prefix.$key, $value, (int) max(1, $minutes * 60));
    }
}
