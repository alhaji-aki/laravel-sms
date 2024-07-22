<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\Exception\FailedSmsException;
use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Stringable;

use function Safe\json_encode;

class SlackSender implements SenderInterface, Stringable
{
    /**
     * The slack configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Create a new slack sender instance.
     *
     * @param  array<string, mixed>  $config
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function send(TextMessage $message): ?SentMessage
    {
        /** @var string */
        $webhookUrl = $this->config['webhook_url'] ?? '';

        if (! $webhookUrl) {
            throw new InvalidArgumentException('You are missing a webhook url in your config.');
        }

        $response = Http::acceptJson()
            ->post($webhookUrl, $payload = $this->getData($message));

        if ($response->failed()) {
            throw new FailedSmsException(
                $response->json('reason', 'Unable to send sms'), // @phpstan-ignore-line
                [
                    'message' => $message->toArray(),
                    'payload' => $payload,
                    'response' => $response->json(),
                ]
            );
        }

        return new SentMessage($message);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(TextMessage $message): array
    {
        $data = [];

        if (isset($this->config['username'])) {
            $data['username'] = $message->getFrom();
        }

        if (isset($this->config['channel'])) {
            $data['channel'] = $this->config['channel'];
        }

        if (isset($this->config['emoji'])) {
            if (false !== ($iconUrl = filter_var($this->config['emoji'], FILTER_VALIDATE_URL))) {
                $data['icon_url'] = $iconUrl;
            } else {
                $data['icon_emoji'] = ":{$this->config['emoji']}:"; // @phpstan-ignore-line
            }
        }

        $data['attachments'][] = [
            'title' => 'Message',
            'fallback' => $message->getMessage(),
            'text' => $message->getMessage(),
            'color' => 'good',
            'fields' => [
                [
                    'title' => 'From',
                    'value' => $message->getFrom(),
                    'short' => false,
                ],
                [
                    'title' => 'To',
                    'value' => is_array($message->getTo()) ? implode(', ', $message->getTo()) : $message->getTo(),
                    'short' => false,
                ],
                [
                    'title' => 'Data',
                    'value' => sprintf('```%s```', json_encode($message->getData())),
                    'short' => false,
                ],
            ],
            'mrkdwn_in' => [
                'fields',
            ],
            'ts' => Carbon::now()->timestamp,
            'footer' => $message->getFrom(),
            'footer_icon' => 'boom',
        ];

        return $data;
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'slack';
    }
}
