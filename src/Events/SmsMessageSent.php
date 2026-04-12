<?php

namespace AlhajiAki\Sms\Events;

use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;

/**
 * @property TextMessage $message
 */
class SmsMessageSent
{
    /**
     * The message that was sent.
     *
     * @var SentMessage
     */
    public $sent;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SentMessage $message)
    {
        $this->sent = $message;
    }
}
