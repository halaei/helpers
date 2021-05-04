<?php

namespace Halaei\Helpers\Listeners;

use Exception;

class RefreshDBConnections
{
    public function handle()
    {
        try {
            \DB::rollBack(0);
        } catch (Exception $e) {
            \DB::reconnect();
            report($e);
        }
    }

    public static function boot()
    {
        \Queue::looping(static::class);
    }
}
