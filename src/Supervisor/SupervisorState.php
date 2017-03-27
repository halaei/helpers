<?php

namespace Halaei\Helpers\Supervisor;

class SupervisorState
{
    /**
     * Whether the supervisor is paused.
     *
     * @var bool
     */
    public $paused = false;

    /**
     * Whether the supervisor should quit.
     *
     * @var bool
     */
    public $shouldQuit = false;

    /**
     * Number of consecutive failed runs.
     *
     * @var int
     */
    public $failed = 0;

    /**
     * @var int|null
     */
    public $lastRestart;

    /**
     * The start time of supervisor loop.
     *
     * @var int
     */
    public $startedAt;

    public function __construct()
    {
        $this->startedAt = time();
    }
}
