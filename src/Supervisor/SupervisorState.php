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
     * @var int|null
     */
    public $lastRestart;

    /**
     * @var false|int
     */
    public $exitStatus = false;
}
