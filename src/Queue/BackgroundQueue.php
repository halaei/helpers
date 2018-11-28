<?php

namespace Halaei\Helpers\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\SyncQueue;

class BackgroundQueue extends SyncQueue implements Queue
{
    protected $queue = [];

    public function __construct(QueueManager $manager)
    {
        $manager->looping(function () {
            $this->handleAll();
        });
    }

    public function setContainer(Container $container)
    {
        parent::setContainer($container);
        if (method_exists($this->container, 'terminating')) {
            $this->container->terminating(function () {
                $this->handleAll();
            });
        }
    }

    /**
     * Get the size of the queue.
     *
     * @param  string $queue
     * @return int
     */
    public function size($queue = null)
    {
        return count($this->queue);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $this->queue[] = [$job, $data, $queue];

        return 0;
    }

    public function handleAll()
    {
        $jobs = $this->queue;
        $this->queue = [];
        foreach ($jobs as [$job, $data, $queue]) {
            parent::push($job, $data, $queue);
        }
    }

    public function __destruct()
    {
        $this->handleAll();
    }
}
