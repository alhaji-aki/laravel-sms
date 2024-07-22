<?php

namespace AlhajiAki\Sms\Exception;

use Throwable;

interface SenderExceptionInterface extends Throwable
{
    public function getDebug(): string;

    public function appendDebug(string $debug): void;
}
