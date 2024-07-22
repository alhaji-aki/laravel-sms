# Laravel Otp Token

This is a to help integrate various sms providers in one project. This allows users to switch between various providers and one api for sending sms notifications without the need for major code changes.

## Installation

You can install the package via composer by running:

```bash
composer require "alhaji-aki/laravel-sms"
```

After the installation has completed, the package will automatically register itself.
Run the following to publish the config file

```bash
php artisan vendor:publish --provider="AlhajiAki\Sms\SmsServiceProvider"
```

<!-- Show config -->

<!-- Usage -->
<!-- as notification -->
<!-- as normal call -->

## Testing

```bash
vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
