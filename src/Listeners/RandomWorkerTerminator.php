<?php

namespace Halaei\Helpers\Listeners;

use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\Queue;

class RandomWorkerTerminator
{
    /**
     * Time to live of the worker.
     *
     * @var int
     */
    private $timeToLive;

    /**
     * Worker start time.
     *
     * @var int
     */
    private $start;

    public function __construct($minTTL = 180, $maxTTL = 240)
    {
        $this->timeToLive = rand($minTTL, $maxTTL);

        $this->start = time();
    }

    public function handle()
    {
        Queue::looping(function () {
            if (time() > $this->start + $this->timeToLive) {
                app(Worker::class)->stop();
            }
        });
    }

    public static function boot($minTTL = 180, $maxTTL = 240)
    {
        $instance = new static($minTTL, $maxTTL);

        Queue::looping(function () use ($instance) {
            $instance->handle();
        });
    }
}
