<?php

namespace Halaei\Helpers\Cache;

use Illuminate\Cache\MemcachedConnector;

class CacheServiceProvider extends \Illuminate\Cache\CacheServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->app->singleton('memcached.connector', function () {
            return new MemcachedConnector;
        });

        $this->registerCommands();
    }
}
