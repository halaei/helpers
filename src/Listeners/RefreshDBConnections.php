<?php

namespace Halaei\Helpers\Listeners;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\Events\JobProcessing;

class RefreshDBConnections
{
    /**
     * @var ExceptionHandler
     */
    protected $exceptions;

    public function __construct(ExceptionHandler $exceptions)
    {
        $this->exceptions = $exceptions;
    }

    public function handle(JobProcessing $event)
    {
        if ($event->connectionName != 'sync') {
            try {
                while (\DB::transactionLevel() > 0) {
                    \DB::rollBack();
                }
            } catch (Exception $e) {
                \DB::reconnect();
                $this->exceptions->report($e);
            }
        }
    }
}
