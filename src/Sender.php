<?php

namespace AlhajiAki\Sms;

use AlhajiAki\Sms\Contracts\Sender as SenderContract;
use AlhajiAki\Sms\Events\SmsMessageSending;
use AlhajiAki\Sms\Events\SmsMessageSent;
use AlhajiAki\Sms\Senders\SenderInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory as QueueContract;
use Illuminate\Support\Traits\Macroable;

class Sender implements SenderContract
{
    use Macroable;

    /**
     * The name that is configured for the sender.
     */
    protected string $name;

    /**
     * The Sms Provider instance.
     *
     * @var \AlhajiAki\Sms\Senders\SenderInterface
     */
    protected $driver;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * The global from.
     */
    protected string $from;

    /**
     * The global to number.
     *
     * @var string|array<int, string>
     */
    protected $to;

    /**
     * The queue factory implementation.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $queue;

    /**
     * Create a new Sender instance.
     *
     * @return void
     */
    public function __construct(string $name, SenderInterface $driver, ?Dispatcher $events = null)
    {
        $this->name = $name;
        $this->events = $events;
        $this->driver = $driver;
    }

    /**
     * Set the global from.
     */
    public function alwaysFrom(string $from): void
    {
        $this->from = $from;
    }

    /**
     * Set the global to number.
     *
     * @param  string|array<int, string>  $to
     */
    public function alwaysTo(string|array $to): void
    {
        $this->to = $to;
    }

    /**
     * Send a new message.
     */
    public function send(string $message, string|array $to, ?string $from = null, array $data = []): ?SentMessage
    {
        $data['sender'] = $this->name;

        $message = $this->createMessage()
            ->when($from, fn ($message) => $message->from($from)) // @phpstan-ignore-line
            ->to($to)
            ->message($message)
            ->data($data);

        // If a global "to" address has been set, we will set that address on the mail
        // message. This is primarily useful during local development in which each
        // message should be delivered into a single mail address for inspection.
        if ($this->to) {
            $this->setGlobalTo($message);
        }

        $data['message'] = $message;

        // Next we will determine if the message should be sent. We give the developer
        // one final chance to stop this message and then we will send it to all of
        // its recipients. We will then fire the sent event for the sent message.
        if ($this->shouldSendMessage($message)) {
            $sentMessage = $this->sendMessage($message);

            if ($sentMessage) {
                $sentMessage = new SentMessage($message);

                $this->dispatchSentEvent($sentMessage);

                return $sentMessage;
            }

            return null;
        }

        return null;
    }

    /**
     * Set the global "to" phone numbers on the given message.
     */
    protected function setGlobalTo(TextMessage $message): void
    {
        $message->forgetTo();

        $message->to($this->to);
    }

    /**
     * Create a new message instance.
     */
    protected function createMessage(): TextMessage
    {
        $message = new TextMessage();

        // If a global from address has been specified we will set it on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We'll just go ahead and push this address.
        if (! empty($this->from)) {
            $message->from($this->from);
        }

        return $message;
    }

    /**
     * Send a message.
     */
    protected function sendMessage(TextMessage $message): ?SentMessage
    {
        $message->ensureValidity();

        try {
            return $this->driver->send($message);
        } finally {
            //
        }
    }

    /**
     * Determines if the sms can be sent.
     */
    protected function shouldSendMessage(TextMessage $message): bool
    {
        if (! $this->events) {
            return true;
        }

        return $this->events->until(
            new SmsMessageSending($message)
        ) !== false;
    }

    /**
     * Dispatch the message sent event.
     */
    protected function dispatchSentEvent(SentMessage $message): void
    {
        $this->events?->dispatch(
            new SmsMessageSent($message)
        );
    }

    /**
     * Get the driver instance.
     *
     * @return \AlhajiAki\Sms\Senders\SenderInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Set the driver instance.
     *
     * @return void
     */
    public function setDriver(SenderInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Set the queue manager instance.
     *
     * @return $this
     */
    public function setQueue(QueueContract $queue)
    {
        $this->queue = $queue;

        return $this;
    }
}
