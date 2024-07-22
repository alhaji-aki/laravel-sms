<?php

namespace AlhajiAki\Sms;

use Illuminate\Support\Str;

class SentMessage
{
    private TextMessage $message;

    private string $messageId;

    private string $debug = '';

    /**
     * @internal
     */
    public function __construct(TextMessage $message)
    {
        $this->message = $message;
        $this->messageId = Str::ulid();
    }

    public function getMessage(): TextMessage
    {
        return $this->message;
    }

    public function setMessageId(string $id): static
    {
        $this->messageId = $id;

        return $this;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getDebug(): string
    {
        return $this->debug;
    }

    public function appendDebug(string $debug): static
    {
        $this->debug .= $debug;

        return $this;
    }
}
