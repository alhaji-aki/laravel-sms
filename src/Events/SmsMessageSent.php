<?php

namespace AlhajiAki\Sms\Events;

use AlhajiAki\Sms\SentMessage;

/**
 * @property \AlhajiAki\Sms\TextMessage $message
 */
class SmsMessageSent
{
    /**
     * The message that was sent.
     *
     * @var \AlhajiAki\Sms\SentMessage
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
