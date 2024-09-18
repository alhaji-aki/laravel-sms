<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\Exception\FailedSmsException;
use AlhajiAki\Sms\Senders\SenderInterface;
use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;
use Illuminate\Support\Facades\Http;

class ArkeselSender implements SenderInterface
{
    /**
     * The Arkesel configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Create a new Arkesel sender instance.
     *
     * @param  array<string, mixed>  $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function send(TextMessage $message): ?SentMessage
    {
        $recipients = is_string($message->getTo()) ? [$message->getTo()] : $message->getTo();

        $payload = [
            'sender' => $message->getFrom() ?: $this->config['sender_id'],
            'message' => $message->getMessage(),
            'recipients' => $recipients,
            'sandbox' => $this->config['sandbox'] ?? false,
        ];

        if ($this->config['sandbox'] ?? false) {
            $payload['sandbox'] = true;
        }

        $response = Http::baseUrl('https://sms.arkesel.com/api/v2/')
            ->asJson()
            ->withHeader('api-key', $this->config['api_key'])
            ->post('sms/send', $payload);

        if ($response->failed() || $response->json('status') !== 'success') {
            throw new FailedSmsException(
                $response->json('message', 'SMS request failed!'), // @phpstan-ignore-line
                [
                    'message' => $message->toArray(),
                    'payload' => $payload,
                    'response' => $response->json(),
                ]
            );
        }

        return (new SentMessage($message))->appendDebug($response->body());
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'arkesel';
    }
}
