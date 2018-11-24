<?php

namespace Halaei\Helpers\Supervisor;

/**
 * @property array $quitOnSignals   (default: [SIGTERM, SIGINT])
 */
trait QuitsOnSignals
{
    /**
     * Indicates if the process should exit.
     *
     * @var bool
     */
    protected $shouldQuit = false;

    /**
     * Signal handlers that was registered before calling to listenToSignals()
     *
     * @var array
     */
    protected $reservedSignalHandlers = [];

    /**
     * Enable async signals for the process and listen to signals for quitting.
     */
    protected function listenToSignals()
    {
        $this->shouldQuit = false;
        pcntl_async_signals(true);
        $signals = property_exists($this, 'quitOnSignals') ? $this->quitOnSignals : [SIGTERM, SIGINT];
        foreach ($signals as $signal) {
            $this->reservedSignalHandlers[$signal] = pcntl_signal_get_handler($signal);
            pcntl_signal($signal, function () {
                $this->shouldQuit = true;
            });
        }
    }

    /**
     * Stop listening to signals and set the reserved signal handlers (if any).
     */
    protected function stopListeningToSignals()
    {
        $this->shouldQuit = false;
        foreach ($this->reservedSignalHandlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }
        $this->reservedSignalHandlers = [];
    }

    /**
     * Exit if a signal has arrived.
     *
     * @param int $status
     */
    protected function quitIfSignaled($status = 0)
    {
        if ($this->shouldQuit) {
            exit($status);
        }
    }
}
