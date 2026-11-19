<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\Exception\FailedSmsException;
use AlhajiAki\Sms\RecipientResult;
use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use function Safe\json_encode;

class AppsnmobileSender implements SenderInterface
{
    /** Orchard's code for a message it accepted. */
    private const ACCEPTED = '082';

    /** Orchard's code for an account that has run out of credit. */
    private const OUT_OF_CREDIT = '076';

    /**
     * The Apps n Mobile configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Create a new Apps n Mobile sender instance.
     *
     * @param  array<string, mixed>  $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Orchard takes one number per request, so each recipient is sent, and answered, on its own.
     *
     * {@inheritdoc}
     */
    public function send(TextMessage $message): ?SentMessage
    {
        $sentMessage = new SentMessage($message);
        $failures = [];

        foreach ($message->recipients() as $phone) {
            $payload = [
                'msg_body' => $message->getMessage(),
                'msg_type' => $this->config['message_type'] ?? 'T',
                'recipient_number' => $phone,
                'sender_id' => $message->getFrom(),
                'unique_id' => Str::random(20),
                'service_id' => $this->config['service_id'],
                'trans_type' => 'SMS',
            ];

            /** @var string $clientId */
            $clientId = $this->config['client_id'];

            /** @var string $secret */
            $secret = $this->config['secret_key'];

            /** @var string $baseUrl */
            $baseUrl = $this->config['base_url'] ?? 'https://orchard-api.anmgw.com';

            $response = Http::baseUrl($baseUrl)
                ->asJson()
                ->acceptJson()
                ->withHeader('Authorization', $clientId.':'.hash_hmac('sha256', json_encode($payload), $secret))
                ->post('sendSms', $payload);

            $sentMessage->appendDebug($response->body()."\n");

            $code = $response->json('resp_code');

            if ($response->ok() && $code === self::ACCEPTED) {
                $sentMessage->addRecipient(RecipientResult::accepted($phone, $payload['unique_id']));

                continue;
            }

            /** @var string $reason */
            $reason = $code === self::OUT_OF_CREDIT
                ? 'The Apps n Mobile account has run out of credit.'
                : $response->json('resp_desc', $response->reason());

            $sentMessage->addRecipient(RecipientResult::refused($phone, $reason));
            $failures[] = ['payload' => $payload, 'response' => $response->json()];
        }

        $accepted = array_filter($sentMessage->getRecipients(), fn (RecipientResult $recipient) => $recipient->accepted);

        if ($accepted === []) {
            throw new FailedSmsException(
                $sentMessage->getRecipients()[0]->reason ?? 'SMS request failed!',
                ['message' => $message->toArray(), 'failures' => $failures]
            );
        }

        return $sentMessage;
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'appsnmobile';
    }
}
