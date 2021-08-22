<?php

namespace Halaei\Helpers\Process;

/**
 * Simplified & safer version of Symfony\Process
 */
class Process
{
    /**
     * @var array
     */
    protected $command;

    /**
     * @var string|null
     */
    protected $cwd;

    /**
     * @var array|null
     */
    protected $env;

    /**
     * @var string|null
     */
    protected $input;

    /**
     * @var int
     */
    protected $inputCursor = 0;

    /**
     * @var float|int|null
     */
    protected $timeout;

    /**
     * @var float
     */
    protected $startedAt;

    /**
     * @var resource|null
     */
    protected $process;

    /**
     * @var array
     */
    protected $pipes = [];

    /**
     * @var array|false
     */
    protected $status = false;

    /**
     * @var ProcessResult|null
     */
    protected $result;

    /**
     * Whether the input is closed by the process.
     *
     * @var boolean
     */
    protected $inputClosed;

    /**
     * The time at which the process timed out.
     *
     * @var float|null
     */
    protected $timedOutAt;

    /**
     * Microseconds to sleep.
     *
     * @var int
     */
    public $usleep = 1000;

    /**
     * Seconds to wait for process to be killed.
     *
     * @var int|float
     */
    public $waitForKill = 3;

    public function __construct(array $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60)
    {
        $this->command = $command;
        $this->cwd = $cwd;
        $this->env = $env;
        $this->input = $input;
        $this->timeout = $timeout;
    }

    /**
     * @return ProcessResult
     * @throws ProcessException
     */
    public function mustRun()
    {
        $result = $this->run();
        if (! $result) {
            throw new ProcessException("Cant start process: ".$this->getCommandLine(), ProcessException::CODE_START_ERROR);
        }
        if ($result->timedOut) {
            throw (new ProcessException("Process timed out after {$this->timeout} seconds: ".$this->getCommandLine(), ProcessException::CODE_TIMEOUT_ERROR))
                ->setResult($result);
        }
        if ($result->exitCode) {
            throw (new ProcessException("Process exited with code {$result->exitCode}: ".$this->getCommandLine(), ProcessException::CODE_EXIT_CODE_ERROR))
                ->setResult($result);
        }
        return $result;
    }

    /**
     * @return ProcessResult
     */
    public function run()
    {
        if (! $this->open()) {
            return null;
        }
        for ($this->status = proc_get_status($this->process); $this->status['running'] && ! $this->result->timedOut; $this->status = proc_get_status($this->process)) {
            $ready = $this->wait();
            try {
                if (! $this->inputClosed && isset($ready[1][0])) {
                    if ($this->inputCursor < strlen($this->input)) {
                        $this->inputCursor += fwrite($this->pipes[0], substr($this->input, $this->inputCursor), strlen($this->input) - $this->inputCursor);
                    }
                    if ($this->inputCursor >= strlen($this->input)) {
                        fclose($this->pipes[0]);
                        $this->inputClosed = true;
                    }
                }
                if (isset($ready[0][0])) {
                    $read = fread($this->pipes[1], 16384);
                    if ($read !== false && strlen($read)) {
                        $this->result->stdOut .= $read;
                    }
                }
                if (isset($ready[0][1])) {
                    $read = fread($this->pipes[2], 16384);
                    if ($read !== false && strlen($read)) {
                        $this->result->stdErr .= $read;
                    }
                }
            } catch (\Exception $e) {
                // Ignore broken pipe
            }
            if ($this->startedAt + $this->timeout < microtime(true)) {
                $this->result->timedOut = true;
            }
        }

        if (! $this->status['running']) {
            $this->result->exitCode = $this->status['exitcode'];
            $this->result->timedOut = false;
        } else {
            $this->kill();
        }

        try {
            stream_set_blocking($this->pipes[1], true);
            stream_set_blocking($this->pipes[2], true);
            // todo: use feof() to handle empty reads in the middle of streams
            while (($read = fread($this->pipes[1], 16384)) !== false && strlen($read)) {
                $this->result->stdOut .= $read;
            }
            while (($read = fread($this->pipes[2], 16384)) !== false && strlen($read)) {
                $this->result->stdErr .= $read;
            }
        } catch (\Exception $e) {
            $this->result->readError = $e;
        }

        foreach ($this->pipes as $key => $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($this->process);

        return $this->result;
    }

    /**
     * Open the process
     *
     * @return boolean true on success.
     */
    protected function open()
    {
        $descriptors = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];
        $this->startedAt = microtime(true);
        // PHP 7.4 accepts array as command arguments and doesn't open process through shell.
        $this->process = proc_open($this->command, $descriptors, $this->pipes, $this->cwd, $this->env);
        if (! is_resource($this->process)) {
            return false;
        }
        $this->result = new ProcessResult();
        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        $this->inputClosed = false;
        return true;
    }

    /**
     * Concurrently run an array of processes.
     *
     * @param static[] $processes
     * @return ProcessResult[]
     */
    public static function runAll(array $processes)
    {
        $results = [];
        $running = [];
        $timedout = [];
        foreach ($processes as $index => $process) {
            if ($process->open()) {
                $running[$index] = $process;
            } else {
                $results[$index] = null;
            }
        }
        while (count($running) || count($timedout)) {
            // 1. Handle running processes
            if (count($running)) {
                $readyPipes = static::selectPipes($running);
                // Write to STDIN
                foreach ($readyPipes[1] as $pipeIndex => $isReady) {
                    preg_match('/(.*)_0$/', $pipeIndex, $matches);
                    $process = $running[$matches[1]];
                    try {
                        if ($process->inputCursor < strlen($process->input)) {
                            $process->inputCursor += fwrite($process->pipes[0], substr($process->input, $process->inputCursor), strlen($process->input) - $process->inputCursor);
                        }
                        if ($process->inputCursor >= strlen($process->input)) {
                            fclose($process->pipes[0]);
                            $process->inputClosed = true;
                        }
                    } catch (\Exception $e) {
                        // Ignore broken pipes.
                    }
                }
                // Read from STDOUT and STDERR
                foreach ($readyPipes[0] as $pipeIndex => $isReady) {
                    preg_match('/(.*)_(1|2)$/', $pipeIndex, $matches);
                    $process = $running[$matches[1]];
                    $pipeId = $matches[2];
                    $pipe = $process->pipes[$pipeId];
                    try {
                        $read = fread($pipe, 16384);
                        if ($read !== false && strlen($read)) {
                            if ($pipeId == 1) {
                                $process->result->stdOut .= $read;
                            } else {
                                $process->result->stdErr .= $read;
                            }
                        }
                    } catch (\Exception $e) {
                        // Ignore broken pipes.
                    }
                }
                // Check running status and timeouts
                foreach ($running as $index => $process) {
                    if (! $process->status['running']) {
                        unset($running[$index]);
                    } elseif ($process->startedAt + $process->timeout < microtime(true)) {
                        $process->result->timedOut = true;
                        $timedout[$index] = $process;
                        unset($running[$index]);
                    }
                }
            }
            // 2. Handle timedout processes
            $timedout = static::signalAll($timedout);
            if (! count($running) && count($timedout)) {
                usleep(1000);
            }
        }
        // Read to the end of files in blocking mode
        // Todo: check if it is possible/required to read the result of closed process in non-blocking mode.
        foreach ($processes as $index => $process) {
            if (array_key_exists($index, $results)) {
                continue;
            }
            if (! $process->status['running']) {
                $process->result->exitCode = $process->status['exitcode'];
            }
            try {
                stream_set_blocking($process->pipes[1], true);
                stream_set_blocking($process->pipes[2], true);
                while (($read = fread($process->pipes[1], 16384)) !== false && strlen($read)) {
                    $process->result->stdOut .= $read;
                }
                while (($read = fread($process->pipes[2], 16384)) !== false && strlen($read)) {
                    $process->result->stdErr .= $read;
                }
            } catch (\Exception $e) {
                $process->result->readError = $e;
            }

            foreach ($process->pipes as $key => $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($process->process);
            $results[$index] = $process->result;
        }
        return $results;
    }

    /**
     * Gets the command line to be executed.
     * Note: This is used only in exception messages.
     *
     * @return string The command to execute
     */
    protected function getCommandLine()
    {
        return implode(' ', array_map([$this, 'escapeArgument'], $this->command));
    }

    /**
     * Escapes a string to be used as a shell argument.
     */
    protected static function escapeArgument(?string $argument): string
    {
        if ('' === $argument || null === $argument) {
            return '""';
        }
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            return "'".str_replace("'", "'\\''", $argument)."'";
        }
        if (false !== strpos($argument, "\0")) {
            $argument = str_replace("\0", '?', $argument);
        }
        if (!preg_match('/[\/()%!^"<>&|\s]/', $argument)) {
            return $argument;
        }
        $argument = preg_replace('/(\\\\+)$/', '$1$1', $argument);

        return '"'.str_replace(['"', '^', '%', '!', "\n"], ['""', '"^^"', '"^%"', '"^!"', '!LF!'], $argument).'"';
    }

    protected function kill()
    {
        $time = microtime(true);
        do {
            @posix_kill($this->status['pid'], SIGTERM);
            usleep($this->usleep);
            $this->status = proc_get_status($this->process);
        } while($this->status['running'] && $time + $this->waitForKill > microtime(true));

        if ($this->status['running']) {
            @posix_kill($this->status['pid'], SIGKILL);
        }
    }

    /**
     * Signal all the timed-out processes once.
     *
     * @param static[] $processes
     * @return static[] return only running processes.
     */
    protected static function signalAll(array $processes)
    {
        $running = [];
        foreach ($processes as $index => $process) {
            $process->result->timedOut = true;
            $hasBeenSignaled = $process->timedOutAt;
            if (! $hasBeenSignaled) {
                $process->timedOutAt = microtime(true);
            }
            if (! $hasBeenSignaled || $process->timedOutAt + $process->waitForKill > microtime(true)) {
                @posix_kill($process->status['pid'], SIGTERM);
                $process->status = proc_get_status($process->process);
                if ($process->status['running']) {
                    $running[$index] = $process;
                }
            } else {
                @posix_kill($process->status['pid'], SIGKILL);
            }
        }
        return $running;
    }

    /**
     * Wait for I/O to be available.
     *
     * @return array
     * Returns an array that indicates which input/output pipes are ready for at least one byte of I/O.
     */
    protected function wait()
    {
        $read = [$this->pipes[1], $this->pipes[2]];
        $write = $this->inputClosed ? [] : [$this->pipes[0]];
        $except = [];
        try {
            stream_select($read, $write, $except, 1, 0);
        } catch (\Exception $e) {
            usleep($this->usleep);
        }
        return [$read, $write];
    }

    /**
     * @param static[] $processes
     */
    protected static function selectPipes(array $processes)
    {
        $read = [];
        $write = [];
        $except = [];
        foreach ($processes as $index => $process) {
            $process->status = proc_get_status($process->process);
            $read[$index.'_1'] = $process->pipes[1];
            $read[$index.'_2'] = $process->pipes[2];
            if (! $process->inputClosed && $process->status['running']) {
                $write[$index.'_0'] = $process->pipes[0];
            }
        }
        try {
            stream_select($read, $write, $except, 1, 0);
        } catch (\Exception $e) {
            usleep(1000);
        }
        return [$read, $write];
    }
}
