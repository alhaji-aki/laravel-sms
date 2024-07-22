<?php

namespace AlhajiAki\Sms;

use AlhajiAki\Sms\Contracts\Factory;
use AlhajiAki\Sms\Notification\Channels\SmsChannel;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        AboutCommand::add('Laravel Sms', fn () => ['Version' => '0.0']);

        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/sms.php' => config_path('sms.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->registerSms();

        $this->registerNotificationChannel();
    }

    /**
     * Register the sms manager and sender instance.
     *
     * @return void
     */
    protected function registerSms()
    {
        $this->app->singleton('sms.manager', function ($app) {
            return new SmsManager($app);
        });

        $this->app->bind('sms', function ($app) {
            return $app->make('sms.manager')->sender();
        });

        $this->app->alias('sms.manager', SmsManager::class);
        $this->app->alias('sms.manager', Factory::class);
    }

    /**
     * Register the `sms` notification channel.
     */
    protected function registerNotificationChannel(): void
    {
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('sms', function ($app) {
                return $app->make(SmsChannel::class);
            });
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['sms.manager', 'sms'];
    }
}
