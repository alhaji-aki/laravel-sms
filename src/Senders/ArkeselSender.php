<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\Exception\FailedSmsException;
use AlhajiAki\Sms\RecipientResult;
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
        $recipients = $message->recipients();
        $payload = $this->payload($message);

        $response = Http::baseUrl('https://sms.arkesel.com/api/v2/')
            ->asJson()
            ->withHeader('api-key', $this->config['api_key'])
            ->post('sms/send', $payload);

        $context = [
            'message' => $message->toArray(),
            'payload' => $payload,
            'response' => $response->json(),
        ];

        if ($response->failed() || $response->json('status') !== 'success') {
            throw new FailedSmsException(
                $response->json('message', 'SMS request failed!'), // @phpstan-ignore-line
                $context
            );
        }

        // Arkesel answers with one id per accepted number, written without its plus.
        $accepted = [];

        foreach ((array) $response->json('data', []) as $row) {
            if (is_array($row) && is_scalar($row['recipient'] ?? null) && is_scalar($row['id'] ?? null)) {
                $accepted[ltrim((string) $row['recipient'], '+')] = (string) $row['id'];
            }
        }

        if ($accepted === []) {
            throw new FailedSmsException('Arkesel accepted none of the recipients.', $context);
        }

        $sentMessage = (new SentMessage($message))->appendDebug($response->body());

        foreach ($recipients as $phone) {
            $id = $accepted[ltrim($phone, '+')] ?? null;

            $sentMessage->addRecipient($id === null
                ? RecipientResult::refused($phone, 'Arkesel did not accept this number.')
                : RecipientResult::accepted($phone, $id));
        }

        return $sentMessage;
    }

    /**
     * The body sent to Arkesel. Extend the sender to add fields such as `callback_url`.
     *
     * @return array<string, mixed>
     */
    protected function payload(TextMessage $message): array
    {
        $payload = [
            'sender' => $message->getFrom() ?: $this->config['sender_id'],
            'message' => $message->getMessage(),
            'recipients' => $message->recipients(),
        ];

        if ($this->config['sandbox'] ?? false) {
            $payload['sandbox'] = true;
        }

        return $payload;
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'arkesel';
    }
}
