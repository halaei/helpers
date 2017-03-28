<?php

namespace Halaei\Helpers\Supervisor\Events;

use Halaei\Helpers\Supervisor\SupervisorOptions;
use Halaei\Helpers\Supervisor\SupervisorState;

class RunFailed extends Looping
{
    /**
     * @var \Throwable
     */
    public $exception;

    /**
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     * @param \Throwable $exception
     */
    public function __construct(SupervisorOptions $options, SupervisorState $state, $exception)
    {
        parent::__construct($options, $state);
        $this->exception = $exception;
    }
}
