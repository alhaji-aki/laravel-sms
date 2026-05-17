<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\Exception\FailedSmsException;
use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;
use Illuminate\Support\Facades\Http;

class WhapiSender implements SenderInterface
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
        $recipient = is_string($message->getTo()) ? $message->getTo() : $message->getTo()[0];

        $payload = [
            'message' => $message->getMessage(),
            'phone_number' => $recipient,
            'app_id' => $this->config['app_id'],
        ];

        $response = Http::baseUrl('https://whaapi.flobaze.com/api')
            ->asJson()
            ->withToken($this->config['api_key'])
            ->post('v1/send-message', $payload);

        if ($response->failed() || ! boolval($response->json('success'))) {
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
        return 'whapi';
    }
}
