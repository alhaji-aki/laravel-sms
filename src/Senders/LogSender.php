<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;
use Psr\Log\LoggerInterface;
use Stringable;

class LogSender implements SenderInterface, Stringable
{
    /**
     * The Logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Create a new log sender instance.
     *
     * @return void
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function send(TextMessage $message): ?SentMessage
    {
        $this->logger->debug((string) $message);

        return new SentMessage($message);
    }

    /**
     * Get the logger for the LogSender instance.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function logger()
    {
        return $this->logger;
    }

    /**
     * Get the string representation of the sender.
     */
    public function __toString(): string
    {
        return 'log';
    }
}
