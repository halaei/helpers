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

    public function setResult(?ProcessResult $result)
    {
        $this->result = $result;
        if ($result && $result->exitCode !== null) {
            $this->message .= sprintf("\n\nOutput:\n================\n%s\n\nError Output:\n================\n%s",
                substr($result->stdOut ?? '', 0, 2048),
                substr($result->stdErr ?? '', 0, 2048)
            );
        }

        return $this;
    }
}
