<?php

namespace AlhajiAki\Sms\Notification\Messages;

class SmsMessage
{
    /**
     * The sender service to use when sending the sms
     */
    public ?string $sender = null;

    /**
     * The "from" address of the message
     */
    public ?string $from = null;

    /**
     * The contents of the message we are sending
     */
    public string $message;

    /**
     * This will contain any extra data to pass to the sender service
     *
     * @var array<mixed, mixed>
     */
    public array $data = [];

    public function __construct(string $message = '')
    {
        $this->message = $message;
    }

    public function message(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function from(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function sender(?string $sender = null): static
    {
        $this->sender = $sender;

        return $this;
    }
}
