<?php

namespace Halaei\Helpers\Process;

class ProcessResult
{
    public $exitCode;
    public $stdOut;
    public $stdErr;
    public $timedOut = false;
    public $readError = null;

    public function __construct($exitCode = null, $stdOut = '', $stdErr = '')
    {
        $this->exitCode = $exitCode;
        $this->stdOut = $stdOut;
        $this->stdErr = $stdErr;
    }
}
