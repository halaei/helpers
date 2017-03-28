<?php

namespace Halaei\Helpers\Supervisor\Events;

use Halaei\Helpers\Supervisor\SupervisorOptions;
use Halaei\Helpers\Supervisor\SupervisorState;

abstract class Looping
{
    /**
     * @var SupervisorOptions
     */
    public $options;
    /**
     * @var SupervisorState
     */
    public $state;

    public function __construct(SupervisorOptions $options, SupervisorState $state)
    {
        $this->options = $options;
        $this->state = $state;
    }
}
