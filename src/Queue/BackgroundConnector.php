<?php

namespace Halaei\Helpers\Queue;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\QueueManager;

class BackgroundConnector implements ConnectorInterface
{
    /**
     * @var QueueManager
     */
    protected $manager;

    public function __construct(QueueManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new BackgroundQueue($this->manager);
    }
}
