<?php

namespace Halaei\Helpers\Supervisor\Events;

class SupervisorStopping
{
    public $status;

    /**
     * @param $status
     */
    public function __construct($status)
    {
        $this->status = $status;
    }
}
