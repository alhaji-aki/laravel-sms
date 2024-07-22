<?php

namespace AlhajiAki\Sms;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Testing\Fakes\MailFake;

/**
 * @method static \AlhajiAki\Sms\Contracts\Sender sender(string|null $name = null)
 * @method static \AlhajiAki\Sms\Sender driver(string|null $driver = null)
 * @method static \AlhajiAki\Sms\Senders\SenderInterface createSender(array $config)
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static void purge(string|null $name = null)
 * @method static \AlhajiAki\Sms\SmsManager extend(string $driver, \Closure $callback)
 * @method static \Illuminate\Contracts\Foundation\Application getApplication()
 * @method static \AlhajiAki\Sms\SmsManager setApplication(\Illuminate\Contracts\Foundation\Application $app)
 * @method static \AlhajiAki\Sms\SmsManager forgetSenders()
 * @method static void alwaysFrom(string $from)
 * @method static void alwaysTo(string|array $to)
 * @method static \AlhajiAki\Sms\SentMessage|null send(string $message, string|array $to, ?string $from = null, array $data = [])
 * @method static \AlhajiAki\Sms\Senders\SenderInterface getDriver()
 * @method static void getDriver(\AlhajiAki\Sms\Senders\SenderInterface $driver)
 * @method static \AlhajiAki\Sms\Sender setQueue(\Illuminate\Contracts\Queue\Factory $queue)
 * @method static void macro(string $name, object|callable $macro, object|callable $macro = null)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \AlhajiAki\Sms\SmsManager
 */
// TODO: implement these
// * @method static void assertSent(string|\Closure $mailable, callable|array|string|int|null $callback = null)
// * @method static void assertNotOutgoing(string|\Closure $mailable, callable|null $callback = null)
// * @method static void assertNotSent(string|\Closure $mailable, callable|array|string|null $callback = null)
// * @method static void assertNothingOutgoing()
// * @method static void assertNothingSent()
// * @method static void assertSentCount(int $count)
// * @method static void assertOutgoingCount(int $count)
// * @method static \Illuminate\Support\Collection sent(string|\Closure $mailable, callable|null $callback = null)
// * @method static bool hasSent(string $mailable)
// * @see \Illuminate\Support\Testing\Fakes\MailFake
class Sms extends Facade
{
    // /**
    //  * Replace the bound instance with a fake.
    //  *
    //  * @return \Illuminate\Support\Testing\Fakes\MailFake
    //  */
    // public static function fake()
    // {
    //     $actualMailManager = static::isFake()
    //         ? static::getFacadeRoot()->manager
    //         : static::getFacadeRoot();

    //     return tap(new MailFake($actualMailManager), function ($fake) {
    //         static::swap($fake);
    //     });
    // }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sms.manager';
    }
}
