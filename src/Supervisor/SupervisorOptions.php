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
     * Whether force the loop to run in maintenance mode.
     *
     * @var bool
     */
    public $force;

    /**
     * Whether to stop on error.
     *
     * @var bool
     */
    public $stopOnError;

    /**
     * Prefer to return instead of calling exit() if possible.
     *
     * @var bool
     */
    public $dontDie;

    /**
     * @param int $timeout
     * @param int|float $memory
     * @param bool $force
     * @param bool $stopOnError
     * @param bool $dontDie
     */
    public function __construct($timeout = 60, $memory = 128, $force = false, $stopOnError = false, $dontDie = false)
    {
        $this->timeout = $timeout;
        $this->memory = $memory;
        $this->force = $force;
        $this->stopOnError = $stopOnError;
        $this->dontDie = $dontDie;
    }
}
