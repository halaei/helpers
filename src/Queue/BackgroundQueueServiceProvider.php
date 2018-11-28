<?php

namespace Halaei\Helpers\Queue;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;

class BackgroundQueueServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot(QueueManager $manager)
    {
        $manager->addConnector('background', function() use ($manager) {
            return new BackgroundConnector($manager);
        });
    }
}
