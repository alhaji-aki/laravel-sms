<?php

namespace AlhajiAki\Sms\Notification\Channels;

use AlhajiAki\Sms\Contracts\Factory as SmsFactory;
use AlhajiAki\Sms\SentMessage;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(
        protected SmsFactory $factory
    ) {}

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     */
    public function send($notifiable, Notification $notification): ?SentMessage
    {
        // @phpstan-ignore-next-line
        if (! $to = $notifiable->routeNotificationFor('sms', $notification)) {
            return null;
        }

        /**
         * @var \AlhajiAki\Sms\Notification\Messages\SmsMessage
         */
        $message = $notification->toSms($notifiable); // @phpstan-ignore-line

        return $this->factory->sender($message->sender ?? null)->send(
            message: $message->message,
            to: $to,
            from: $message->from ?? null,
            data: $message->data ?? [],
        );
    }
}
