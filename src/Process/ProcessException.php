<?php

namespace Halaei\Helpers\Process;

use Exception;

class ProcessException extends Exception
{
    const CODE_START_ERROR = 1;
    const CODE_TIMEOUT_ERROR = 2;
    const CODE_EXIT_CODE_ERROR = 3;

    /**
     * @var ProcessResult|null
     */
    public $result;

    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
