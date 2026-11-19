<?php

namespace AlhajiAki\Sms;

use Illuminate\Support\Str;

class SentMessage
{
    private TextMessage $message;

    private string $messageId;

    private string $debug = '';

    /**
     * @var list<RecipientResult>
     */
    private array $recipients = [];

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

    /**
     * What the provider said about each recipient. Empty when the sender reports nothing per recipient.
     *
     * @return list<RecipientResult>
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function addRecipient(RecipientResult $recipient): static
    {
        $this->recipients[] = $recipient;

        return $this;
    }

    /**
     * Marks every recipient of the message as accepted, for senders that take the whole message or none of it.
     */
    public function acceptAll(?string $providerMessageId = null): static
    {
        foreach ($this->message->recipients() as $phone) {
            $this->addRecipient(RecipientResult::accepted($phone, $providerMessageId));
        }

        return $this;
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
