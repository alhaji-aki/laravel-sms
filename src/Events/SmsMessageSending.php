<?php

namespace AlhajiAki\Sms\Events;

use AlhajiAki\Sms\TextMessage;

class SmsMessageSending
{
    /**
     * The Text message instance.
     *
     * @var \AlhajiAki\Sms\TextMessage
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TextMessage $message)
    {
        $this->message = $message;
    }
}
