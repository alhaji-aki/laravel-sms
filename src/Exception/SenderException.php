<?php

namespace AlhajiAki\Sms\Exception;

use RuntimeException;

class SenderException extends RuntimeException implements SenderExceptionInterface
{
    private string $debug = '';

    public function getDebug(): string
    {
        return $this->debug;
    }

    public function appendDebug(string $debug): void
    {
        $this->debug .= $debug;
    }
}
