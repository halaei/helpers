<?php

namespace Halaei\Helpers\Supervisor;

class SupervisorOptions
{
    /**
     * The number of seconds a supervised command can run.
     *
     * @var int
     */
    public $timeout;

    /**
     * The memory limit in megabytes.
     *
     * @var int|float
     */
    public $memory;

    /**
     * The hard memory limit in megabytes.
     *
     * @var null|int|float
     */
    public $maxMemory;

    /**
     * Whether force the loop to run in maintenance mode.
     *
     * @var bool
     */
    public $force;

    /**
     * Number of consecutive failed runs allowed before giving up and terminating the loop.
     *
     * @var int
     */
    public $tries;

    /**
     * Time to live in seconds.
     *
     * @var int
     */
    public $ttl;

    /**
     * @param int $timeout
     * @param int|float $memory
     * @param int|float $maxMemory
     * @param bool $force
     * @param int $tries
     * @param int $ttl
     */
    public function __construct($timeout = 60, $memory = 128, $maxMemory = 0, $force = false, $tries = 0, $ttl = 0)
    {
        $this->timeout = $timeout;
        $this->memory = $memory;
        $this->maxMemory = $maxMemory > 0 ? max($this->memory + 1, $maxMemory) : 0;
        $this->force = $force;
        $this->tries = $tries;
        $this->ttl = $ttl;
    }
}
