<?php

namespace AlhajiAki\Sms;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use JsonSerializable;
use Propaganistas\LaravelPhone\PhoneNumber;
use Stringable;

use function Safe\json_encode;

/** @implements Arrayable<string, mixed> */
class TextMessage implements Arrayable, JsonSerializable, Stringable
{
    use Conditionable;

    /**
     * The "from" address of the message. This can be a phone number or alpha numeric character
     */
    protected string $from;

    /**
     * The recipients of the message. These should be valid phone numbers
     *
     * @var string|array<int, string>
     */
    protected string|array $to = '';

    /**
     * The message we are sending
     */
    protected string $message;

    /**
     * Any extra data to pass to the sender
     *
     * @var array<mixed, mixed>
     */
    protected array $data = [];

    public function getFrom(): string
    {
        return $this->from;
    }

    public function from(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return string|array<int, string>
     */
    public function getTo(): array|string
    {
        return $this->to;
    }

    /**
     * @param  string|array<int, string>  $to
     */
    public function to(string|array $to): static
    {
        if (is_string($to)) {
            $this->to = $this->formatTo($to);

            return $this;
        }

        $recipients = [];
        foreach ($to as $recipient) {
            $recipients[] = $this->formatTo($recipient);
        }

        $this->to = $recipients;

        return $this;
    }

    /**
     * Remove all "to" phone numbers from the message.
     */
    public function forgetTo(): static
    {
        $this->to = [];

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function message(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array<mixed, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param  array<mixed, mixed>  $data
     */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function ensureValidity(): void
    {
        if (! $this->from) {
            throw new InvalidArgumentException('A message must have a from address.');
        }

        if (! $this->to) {
            throw new InvalidArgumentException('A message must have at least one recipient.');
        }

        if (! $this->message) {
            throw new InvalidArgumentException('A message must have a message content.');
        }
    }

    protected function formatTo(string $to): string
    {
        try {
            return (new PhoneNumber($to))->formatE164();
        } catch (Exception) {
            throw new InvalidArgumentException(sprintf('Invalid recipient "%s": Please make sure recipient is an international phone number like +3212345678.', $to));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray()
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    /**
     * Convert message instance to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->jsonSerialize(), 0);
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
