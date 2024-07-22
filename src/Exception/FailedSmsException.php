<?php

namespace AlhajiAki\Sms\Exception;

use Exception;

class FailedSmsException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message, public array $context)
    {
        parent::__construct($message);
    }

    /**
     * Get the exception's context information.
     *
     * @return array<string, mixed>
     */
    public function context()
    {
        return $this->context;
    }
}
