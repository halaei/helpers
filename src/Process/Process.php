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
     * @var string|resource|null
     */
    protected $input;

    /**
     * @var int
     */
    protected $inputCursor = 0;

    protected $inputBuffer;
    /**
     * @var bool
     */
    protected $inputClosed;

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
        if (! $this->start()) {
            return null;
        }
        $this->inputClosed = false;
        $this->inputBuffer = '';
        for ($this->status = proc_get_status($this->process); $this->status['running'] && ! $this->result->timedOut; $this->status = proc_get_status($this->process)) {
            $ready = $this->wait();
            try {
                if (! $this->inputClosed && isset($ready[1][0])) {
                    $this->write();
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
            if (! is_null($this->timeout) && $this->startedAt + $this->timeout < microtime(true)) {
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

    protected function start()
    {
        $descriptors = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];
        $this->startedAt = microtime(true);
        // 'exec' is used to make sure the process is the immediate child, otherwise it will be the child of a child sh process.
        $this->process = proc_open('exec '.$this->getCommandLine(), $descriptors, $this->pipes, $this->cwd, $this->env);
        if (! is_resource($this->process)) {
            return false;
        }
        $this->result = new ProcessResult();
        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }
        return true;
    }

    /**
     * Write the next chunk of data to the process input.
     */
    protected function write()
    {
        if (is_resource($this->input)) {
            // Read to the buffer and check for EOF
            if ($this->inputCursor >= strlen($this->inputBuffer)) {
                $data = fread($this->input, 128 * 1024);
                if ($data !== false && strlen($data)) {
                    $this->inputBuffer = $data;
                    $this->inputCursor = 0;
                } elseif (feof($this->input)) {
                    fclose($this->pipes[0]);
                    $this->inputClosed = true;
                }
            }
            // Write from buffer to pipe
            if ($this->inputCursor < strlen($this->inputBuffer)) {
                $this->inputCursor += fwrite($this->pipes[0], substr($this->inputBuffer, $this->inputCursor), strlen($this->inputBuffer) - $this->inputCursor);
            }
            return;
        }
        // Read from string input
        if ($this->inputCursor < strlen($this->input)) {
            $this->inputCursor += fwrite($this->pipes[0], substr($this->input, $this->inputCursor), strlen($this->input) - $this->inputCursor);
        }
        if ($this->inputCursor >= strlen($this->input)) {
            fclose($this->pipes[0]);
            $this->inputClosed = true;
        }
    }

    /**
     * Gets the command line to be executed.
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

    protected function wait()
    {
        $read = [$this->pipes[1], $this->pipes[2]];
        $write = $this->inputClosed ? [] : [$this->pipes[0]];
        $except = [];
        try {
            stream_select($read, $write, $except, 1, 0);
            return [$read, $write];
        } catch (\Exception $e) {
            usleep($this->usleep);
            return $this->inputClosed ? [[true, true], []] : [[true, true], [true]];
        }
    }
}
