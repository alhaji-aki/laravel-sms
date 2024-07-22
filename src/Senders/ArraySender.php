<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;
use Illuminate\Support\Collection;

class ArraySender implements SenderInterface
{
    /**
     * The collection of messages.
     *
     * @var \Illuminate\Support\Collection<int, SentMessage>
     */
    protected $messages;

    /**
     * Create a new array sender instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = new Collection;
    }

    /**
     * {@inheritdoc}
     */
    public function send(TextMessage $message): ?SentMessage
    {
        return $this->messages[] = new SentMessage($message);
    }

    /**
     * Retrieve the collection of messages.
     *
     * @return \Illuminate\Support\Collection<int, SentMessage>
     */
    public function messages(): Collection
    {
        return $this->messages;
    }

    /**
     * Clear all of the messages from the local collection.
     *
     * @return \Illuminate\Support\Collection<int, SentMessage>
     */
    public function flush(): Collection
    {
        return $this->messages = new Collection;
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'array';
    }
}
