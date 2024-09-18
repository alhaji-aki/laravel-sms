<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Sender
    |--------------------------------------------------------------------------
    |
    | This option controls the default sms sender that is used to send all text
    | messages unless another sender is explicitly specified when sending
    | the message. All additional senders can be configured within the
    | "senders" array. Examples of each type of senders are provided.
    |
    */

    'default' => env('SMS_SENDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all text messages sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all messages that are sent by your application.
    |
    */

    'from' => env('SMS_FROM_NAME', 'Example'),

    /*
    |--------------------------------------------------------------------------
    | Sender Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the senders used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | This package supports a variety of sms "senders" that can be used
    | when delivering a text message. You may specify which one you're using for
    | your senders below. You may also add additional senders if needed.
    |
    | Supported: "hellio", "frog_sms", "arkesel", "log", "array", "slack", "failover",
    |            "roundrobin"
    |
    */

    'senders' => [
        'hellio' => [
            'sender' => 'hellio',
            'client_id' => env('HELLIO_CLIENT_ID'),
            'app_secret' => env('HELLIO_APP_SECRET'),
            'from' => env('HELLIO_SENDER_ID'),
        ],

        'frog_sms' => [
            'sender' => 'frog_sms',
            'username' => env('FROG_SMS_USERNAME'),
            'password' => env('FROG_SMS_PASSWORD'),
            'from' => env('FROG_SMS_SENDER_ID'),
            'service_type' => 'SMS',
            'message_type' => env('FROG_SMS_MESSAGE_TYPE', 'text'),
        ],

        'arkesel' => [
            'sender' => 'arkesel',
            'sender_id' => env('ARKESEL_SENDER_ID'),
            'api_key' => env('ARKESEL_API_KEY'),
            'sandbox' => (bool) env('ARKESEL_SANDBOX', false),
        ],

        'slack' => [
            'sender' => 'slack',
            'webhook_url' => env('SMS_SLACK_WEBHOOK_URL'),
            'from' => env('SMS_SLACK_USERNAME'),
            'emoji' => env('SMS_LOG_SLACK_EMOJI'),
        ],

        'log' => [
            'sender' => 'log',
            'channel' => env('SMS_LOG_CHANNEL'),
        ],

        'array' => [
            'sender' => 'array',
        ],

        'failover' => [
            'sender' => 'failover',
            'senders' => [
                'log',
                'array',
            ],
        ],

        'roundrobin' => [
            'sender' => 'roundrobin',
            'senders' => [
                'log',
                'array',
            ],
        ],

    ],

];
