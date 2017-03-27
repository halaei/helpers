<?php

namespace Halaei\Helpers\Supervisor;

use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Events\Dispatcher;
use Closure;

class Supervisor
{
    /**
     * @var CacheContract
     */
    protected $cache;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var ExceptionHandler
     */
    private $exceptions;

    /**
     * @param CacheContract $cache
     * @param Dispatcher $events
     * @param ExceptionHandler $exceptions
     */
    public function __construct(CacheContract $cache, Dispatcher $events, ExceptionHandler $exceptions)
    {
        $this->cache = $cache;
        $this->events = $events;
        $this->exceptions = $exceptions;
    }

    /**
     * Run and monitor a command in a loop.
     *
     * @param Closure|string|object $command
     * @param SupervisorOptions $options
     */
    public function supervise($command, SupervisorOptions $options)
    {
        $command = $this->resolveCommand($command);

        $state = new SupervisorState();

        $this->listenForSignals($state);
        $this->setMemoryLimit($options->maxMemory);

        $state->lastRestart = $this->getTimestampOfLastRestart();

        while (true) {
            $this->registerTimeoutHandler($options);

            // Before running any command, we will make sure this supervisor is not paused and
            // if it is we will just pause for a given amount of time and
            // make sure we do not need to kill this process off completely.
            if (!$this->shouldRun($options, $state)) {
                $this->pause($options, $state);

                continue;
            }

            $this->run($command, $state);

            // Finally, we will check to see if we have exceeded our memory limits or if
            // this process should restart based on other indications. If so, we'll stop
            // this process and let whatever is "monitoring" it restart the process.
            $this->stopIfNecessary($options, $state);
        }
    }

    /**
     * Run the closure and handle exceptions.
     *
     * @param Closure $closure
     * @param SupervisorState $state
     */
    protected function run(Closure $closure, SupervisorState $state)
    {
        try {
            $closure();
            $state->failed = 0;
        } catch (\Exception $e) {
            $state->failed++;
            $this->exceptions->report($e);
        } catch (\Throwable $e) {
            $state->failed++;
            $this->exceptions->report($e);
        }
    }

    /**
     * Resolve the supervised command into a closure.
     *
     * @param Closure|string|object $command
     *
     * @return Closure
     */
    protected function resolveCommand($command)
    {
        if ($command instanceof Closure) {
            return $command;
        }

        if (is_string($command)) {
            $command = app($command);
        }

        return function () use ($command) {
            dispatch($command);
        };
    }

    /**
     * Enable async signals for the process.
     *
     * @param SupervisorState $state
     */
    protected function listenForSignals(SupervisorState $state)
    {
        if ($this->supportsAsyncSignals()) {
            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, function () use ($state) {
                $state->shouldQuit = true;
            });

            pcntl_signal(SIGUSR2, function () use ($state) {
                $state->paused = true;
            });

            pcntl_signal(SIGCONT, function () use ($state) {
                $state->paused = false;
            });
        }
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals()
    {
        return version_compare(PHP_VERSION, '7.1.0') >= 0 &&
            extension_loaded('pcntl');
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastRestart()
    {
        if ($this->cache) {
            return $this->cache->get('illuminate:queue:restart');
        }
    }

    /**
     * Register the worker timeout handler (PHP 7.1+).
     *
     * @param  SupervisorOptions  $options
     * @return void
     */
    protected function registerTimeoutHandler(SupervisorOptions $options)
    {
        if ($options->timeout > 0 && $this->supportsAsyncSignals()) {
            // We will register a signal handler for the alarm signal so that we can kill this
            // process if it is running too long because it has frozen. This uses the async
            // signals supported in recent versions of PHP to accomplish it conveniently.
            pcntl_signal(SIGALRM, function () {
                $this->kill(1);
            });

            pcntl_alarm($options->timeout);
        }
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     *
     * @return bool
     */
    protected function shouldRun(SupervisorOptions $options, SupervisorState $state)
    {
        return ! ((! $options->force && app()->isDownForMaintenance()) ||
            $state->paused ||
            $this->events->until(new Events\Looping) === false);
    }

    /**
     * Determine if the queue worker should restart.
     *
     * @param  int|null  $lastRestart
     * @return bool
     */
    protected function shouldRestart($lastRestart)
    {
        return $this->getTimestampOfLastRestart() != $lastRestart;
    }

    /**
     * This sets the maximum amount of memory in bytes that is allowed to be allocated.
     *
     * @param null|int|float $maxMemory
     */
    protected function setMemoryLimit($maxMemory)
    {
        if ($maxMemory) {
            ini_set('memory_limit', (int) $maxMemory * 1024 * 1024);
        }
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int   $memoryLimit
     *
     * @return bool
     */
    protected function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Determine if the ttl has been reached.
     *
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     *
     * @return bool
     */
    protected function ttlExceeded(SupervisorOptions $options, SupervisorState $state)
    {
        return $options->ttl > 0 && time() > $state->startedAt + $options->ttl;
    }

    /**
     * Determine if too many consecutive failed attempts has happened.
     *
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     *
     * @return bool
     */
    private function failedExceeded(SupervisorOptions $options, SupervisorState $state)
    {
        return $options->tries > 0 && $options->tries < $state->failed;
    }

    /**
     * Stop the process if necessary.
     *
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     */
    protected function stopIfNecessary(SupervisorOptions $options, SupervisorState $state)
    {
        if ($state->shouldQuit || $this->ttlExceeded($options, $state) || $this->shouldRestart($state->lastRestart)) {
            $this->stop();
        } elseif ($this->memoryExceeded($options->memory)) {
            $this->stop(12);
        } elseif ($this->failedExceeded($options, $state)) {
            $this->stop(1);
        }
    }

    /**
     * Pause the worker for the current loop.
     *
     * @param SupervisorOptions $options
     * @param SupervisorState $state
     */
    protected function pause(SupervisorOptions $options, SupervisorState $state)
    {
        sleep(1);

        $this->stopIfNecessary($options, $state);
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @param  int  $status
     * @return void
     */
    protected function stop($status = 0)
    {
        $this->events->fire(new Events\WorkerStopping);

        exit($status);
    }

    /**
     * Kill the process.
     *
     * @param  int  $status
     * @return void
     */
    protected function kill($status = 0)
    {
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }
}
