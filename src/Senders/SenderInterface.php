<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;

interface SenderInterface
{
    public function send(TextMessage $message): ?SentMessage;
}
