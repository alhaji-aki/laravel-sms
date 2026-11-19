<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\Exception\FailedSmsException;
use AlhajiAki\Sms\RecipientResult;
use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FrogSmsSender implements SenderInterface
{
    /**
     * The frog sms configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Create a new frog_sms sender instance.
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
        $destinations = collect($message->recipients())
            ->map(fn (string $destination) => ['destination' => $destination, 'msgid' => Str::random(10)])
            ->all();

        $response = Http::baseUrl('https://frog.wigal.com.gh/api/v2/')
            ->post('sendmsg', $payload = [
                'senderid' => $message->getFrom(),
                'destinations' => $destinations,
                'message' => $message->getMessage(),
                'service' => $this->config['service_type'],
                'smstype' => $this->config['message_type'],
                'username' => $this->config['username'],
                'password' => $this->config['password'],
            ]);

        if ($response->failed() || $response->json('status') !== 'ACCEPTED') {
            throw new FailedSmsException(
                $response->json('reason', 'Unable to send sms'), // @phpstan-ignore-line
                [
                    'message' => $message->toArray(),
                    'payload' => Arr::except($payload, ['username', 'password']),
                    'response' => $response->json(),
                ]
            );
        }

        $sentMessage = (new SentMessage($message))->appendDebug($response->body());

        foreach ($destinations as $destination) {
            $sentMessage->addRecipient(RecipientResult::accepted($destination['destination'], $destination['msgid']));
        }

        return $sentMessage;
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'frog_sms';
    }
}
