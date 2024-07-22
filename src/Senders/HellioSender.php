<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;
use NotificationChannels\Hellio\Clients\HellioSMSClient;
use NotificationChannels\Hellio\HellioMessage;

class HellioSender implements SenderInterface
{
    /**
     * The Hellio sms client.
     */
    protected HellioSMSClient $client;

    /**
     * The hellio configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Create a new hellio sender instance.
     *
     * @param  array<string, mixed>  $config
     * @return void
     */
    public function __construct(HellioSMSClient $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function send(TextMessage $message): ?SentMessage
    {
        $hellioMessage = new HellioMessage(
            $message->getFrom(),
            is_array($message->getTo()) ? implode(',', $message->getTo()) : $message->getTo(),
            $message->getMessage(),
            $message->getData()['messageType'] ?? $message->getData()['message_type'] ?? $this->config['message_type'] ?? 0 // @phpstan-ignore-line
        );

        $response = $this->client->send($hellioMessage)->getBody()->getContents();

        return (new SentMessage($message))->appendDebug($response);
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'hellio';
    }
}
