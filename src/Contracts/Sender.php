<?php

namespace AlhajiAki\Sms\Contracts;

use AlhajiAki\Sms\SentMessage;

interface Sender
{
    /**
     * Send a new message.
     *
     * @param  string|array<int, string>  $to
     * @param  array<mixed, mixed>  $data
     */
    public function send(string $message, string|array $to, ?string $from = null, array $data = []): ?SentMessage;
}
