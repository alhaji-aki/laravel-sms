<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;
use Stringable;

interface SenderInterface extends Stringable
{
    public function send(TextMessage $message): ?SentMessage;
}
