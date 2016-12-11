<?php

namespace Halaei\Helpers\Listeners;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;

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

    public function handle()
    {
        try {
            while (\DB::transactionLevel() > 0) {
                \DB::rollBack();
            }
        } catch (Exception $e) {
            \DB::reconnect();
            $this->exceptions->report($e);
        }
    }

    public static function boot()
    {
        \Queue::looping(static::class);
    }
}
