# Laravel SMS

Laravel SMS is a package that provides a simple and flexible way to send SMS messages from your Laravel application. It supports multiple SMS providers and allows you to easily switch between them.

## Installation

You can install the package via composer by running:

```bash
composer require "alhaji-aki/laravel-sms"
```

## Configuration

After the installation has completed, the package will automatically register itself.
Run the following to publish the config file

```bash
php artisan vendor:publish --provider="AlhajiAki\Sms\SmsServiceProvider"
```

This will create a `sms.php` file in your config folder where you can configure your SMS providers and other settings. The config file looks like this

```php
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
    | Supported: "hellio", "log", "array", "slack", "failover", "roundrobin"
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
```

The package comes preconfigured for sms providers like [Hellio](https://helliomessaging.com), [Wigal](https://frog.wigal.com.gh), Slack, Log and array.

### Failover Configuration

The failover mechanism allows you to define multiple providers to be used in case the primary provider fails. The configuration array for your application's failover sender should contain an array of senders that reference the order in which configured senders should be chosen for delivery:

```php
'senders' => [
    'failover' => [
        'sender' => 'failover',
        'senders' => [
            'log',
            'slack',
        ],
    ],
 
    // ...
],
```

Once your failover sender has been defined, you should set this sender as the default sender used by your application by specifying its name as the value of the `default` configuration key within your application's `sms` configuration file:

```php
'default' => env('SMS_SENDER', 'failover'),
```

### Round Robin Configuration

The `roundrobin` sender allows you to distribute SMS sending across multiple senders to balance the load. To get started, define a sender within your application's `sms` configuration file that uses the `roundrobin` sender. The configuration array for your application's `roundrobin` sender should contain an array of `senders` that reference which configured senders should be used for delivery:

```php
'senders' => [
    'roundrobin' => [
        'sender' => 'roundrobin',
        'senders' => [
            'log',
            'array',
        ],
    ],
 
    // ...
],
```

### Setting the `FROM`

This package allows you to set the `from` address of your sms in two ways the global way or per sender.

#### Using a Global `from` Address

If your application uses the same "from" address for all of its sms, it can become cumbersome to add it to each sender. Instead, you may specify a global "from" address in your `config/sms.php` configuration file. This address will be used if no other "from" address is specified when sending the sms:

```php
'from' => env('SMS_FROM_NAME', 'Example'),
```

#### Using Sender Level `from` Address

If each sender has their own "from" address, you can specify it in the sender's configuration in the `config/sms.php` configuration file. This address will be used if no other "from" address is specified when sending the sms:

```php
'senders' => [
    'hellio' => [
        'sender' => 'hellio',
        'client_id' => env('HELLIO_CLIENT_ID'),
        'app_secret' => env('HELLIO_APP_SECRET'),
        'from' => env('HELLIO_SENDER_ID'),
    ],
 
    // ...
],
```

You can also specify a `from` address when sending the sms. The `from` specified when sending the message takes precedence over the `from` set at the Sender level in the sender's configuration. This also takes precedence over the global `from` set in the `config/sms.php` configuration file.

## Usage

### In a notification class

To use this package in your notifications, in your notifiable model, make sure to include a `routeNotificationForSms()` method, which returns a phone number. Like below:

```php
class User extends Authenticatable
{
    use Notifiable;

    /**
     * Route notifications for the SMS channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForSms($notification)
    {
        return $this->phone_number; 
    }
}
```

Then in your notification class can use the channel `sms` in your `via()` method:

```php
use AlhajiAki\Sms\Notification\Messages\SmsMessage;
use Illuminate\Notifications\Notification;

class AccountApproved extends Notification
{
    public function via($notifiable)
    {
        return ['sms'];
    }

    public function toSms($notifiable)
    {
        return (new SmsMessage())
            ->message("Hello sms notification channel");
    }
}
```

The `\AlhajiAki\Sms\Notification\Messages\SmsMessage` class provides the following method

- `sender()`: Use this method when you want to change the default sender set in the `config/sms.php` file. This method accepts a string or null.
- `from()`: Use this method to set the `from` address of the message. This method accepts a string.
- `data()`: The data method provides a means for sending data that might be useful to sender class at the point of sending a message. This could be used to set the message type or any relevant information needed to be able to send the sms by sender.

### Using the facade

The package provides an `Sms` facade which can be used to send sms like below:

```php
use AlhajiAki\Sms\Sms;

Sms::send('Hello sms facade', '+3112345678');
```

If you want to use a different sender to send an sms, you can do so like below:

```php
use AlhajiAki\Sms\Sms;

Sms::sender('slack')->send('Hello sms facade', '+3112345678');
```

The `send()` method of accepts 4 parameters explained below:

- `$message`: The message to be sent. This is a string.
- `$to`: The receipient(s) fo the message. This is either a string or an array.
- `$from`: The from address. This is a string or null. If it is not provided the sender's `from` or the global `from` set in the `config/sms.php` file will be used.
- `$data`: The data to be sent to the sender

## Custom Senders

You may wish to write your own senders to deliver sms via other services that this package does not support out of the box. To get started, define a class that extends the `AlhajiAki\Sms\Senders\SenderInterface` class. Then, implement the `send()` and `__toString()` methods on your sender:

```php
use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;

class TwilioSender implements SenderInterface
{
    /**
     * Create a new Twilio sender instance.
     */
    public function __construct(protected array $config) {
    }

    /**
     * {@inheritdoc}
     */
    public function send(TextMessage $message): ?SentMessage
    {
        // Implement the logic to send SMS via your custom sender
    }
 
    /**
     * Get the string representation of the sender.
     */
    public function __toString(): string
    {
        return 'twilio';
    }
}
```

Once you've defined your custom sender, you may register it via the `extend` method provided by the `Sms` facade. Typically, this should be done within the boot method of your application's `AppServiceProvider` service provider. A `$config` argument will be passed to the closure provided to the `extend` method. This argument will contain the configuration array defined for the sender in the application's `config/sms.php` configuration file:

```php
use App\Sms\TwilioSender;
use AlhajiAki\Sms\Sms;

/**
 * Bootstrap any application services.
 */
public function boot(): void
{
    Sms::extend('twilio', function (array $config = []) {
        return new TwilioSender(/* ... */);
    });
}
```

Once your custom sender has been defined and registered, you may create a sender definition within your application's `config/sms.php` configuration file that utilizes the new sender:

```php
'twilio' => [
    'sender' => 'twilio',
    // ...
],
```

## TODOs

- [ ] Add SmsFake to help testing sms sending
- [ ] Write documentation for testing sms sending
- [ ] Write tests for the entire package

## Testing

```bash
vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
