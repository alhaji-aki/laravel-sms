<?php

namespace AlhajiAki\Sms;

use AlhajiAki\Sms\Contracts\Factory as FactoryContract;
use AlhajiAki\Sms\Contracts\Sender as SenderContract;
use AlhajiAki\Sms\Senders\ArraySender;
use AlhajiAki\Sms\Senders\FailoverSender;
use AlhajiAki\Sms\Senders\FrogSmsSender;
use AlhajiAki\Sms\Senders\HellioSender;
use AlhajiAki\Sms\Senders\LogSender;
use AlhajiAki\Sms\Senders\RoundRobinSender;
use AlhajiAki\Sms\Senders\SenderInterface;
use AlhajiAki\Sms\Senders\SlackSender;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use NotificationChannels\Hellio\Clients\HellioSMSClient;
use Psr\Log\LoggerInterface;

/**
 * @mixin \AlhajiAki\Sms\Sender
 */
class SmsManager implements FactoryContract
{
    /**
     * The array of resolved senders.
     *
     * @var array<string, SenderContract>
     */
    protected array $senders = [];

    /**
     * The registered custom driver creators.
     *
     * @var array<string, callable>
     */
    protected $customCreators = [];

    /**
     * Create a new Sms manager instance.
     *
     * @return void
     */
    public function __construct(protected Application $app) {}

    /**
     * Get a sender instance by name.
     */
    public function sender(?string $name = null): Sender|SenderContract
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->senders[$name] = $this->get($name);
    }

    /**
     * Get a sender driver instance.
     */
    public function driver(?string $driver = null): Sender|SenderContract
    {
        return $this->sender($driver);
    }

    /**
     * Attempt to get the sender from the local cache.
     */
    protected function get(string $name): SenderContract
    {
        return $this->senders[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given sender.
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): Sender
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Sms sender [{$name}] is not defined.");
        }

        // Once we have created the sender instance we will set a container instance
        // on the sender. This allows us to resolve sender classes via containers
        // for maximum testability on said classes instead of passing Closures.
        $sender = new Sender(
            $name,
            $this->createSender($config),
            $this->app['events'] // @phpstan-ignore-line
        );

        if ($this->app->bound('queue')) {
            $sender->setQueue($this->app['queue']); // @phpstan-ignore-line
        }

        // Next we will set all of the global addresses on this sender, which allows
        // for easy unification of all "from" addresses as well as easy debugging
        // of sent messages since these will be sent to a single email address.
        foreach (['from', 'to'] as $type) {
            $this->setGlobalAddress($sender, $config, $type);
        }

        return $sender;
    }

    /**
     * Create a new sender instance.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws \InvalidArgumentException
     */
    public function createSender(array $config): SenderInterface
    {
        /** @var string|null */
        $driver = $config['sender'] ?? null;

        if (isset($this->customCreators[$driver])) {
            return call_user_func($this->customCreators[$driver], $config);
        }

        if (
            trim($driver ?? '') === '' ||
            ! method_exists($this, $method = 'create'.ucfirst(Str::camel($driver ?? '')).'Sender')
        ) {
            throw new InvalidArgumentException("Unsupported sms sender [{$driver}].");
        }

        /** @var \AlhajiAki\Sms\Senders\SenderInterface */
        return $this->{$method}($config);
    }

    /**
     * Create an instance of the Hellio Sender driver.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createHellioSender(array $config): HellioSender
    {
        return new HellioSender(new HellioSMSClient(
            $config['client_id'] ?? '', // @phpstan-ignore-line
            $config['app_secret'] ?? '', // @phpstan-ignore-line
            new \GuzzleHttp\Client()
        ), $config);
    }

    /**
     * Create an instance of the Frog Sms Sender driver.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createFrogSmsSender(array $config): FrogSmsSender
    {
        return new FrogSmsSender($config);
    }

    /**
     * Create an instance of the Slack Sender driver.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createSlackSender(array $config): SlackSender
    {
        return new SlackSender($config);
    }

    /**
     * Create an instance of the Failover Sender driver.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createFailoverSender(array $config): FailoverSender
    {
        $drivers = [];

        /** @var array<int, string> */
        $senders = $config['senders'] ?? [];

        foreach ($senders as $name) {
            $config = $this->getConfig($name);

            if (is_null($config)) {
                throw new InvalidArgumentException("Sms sender [{$name}] is not defined.");
            }

            $drivers[] = $this->createSender($config);
        }

        return new FailoverSender($drivers);
    }

    /**
     * Create an instance of the Roundrobin Sender driver.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createRoundrobinSender(array $config): RoundRobinSender
    {
        $drivers = [];

        /** @var array<int, string> */
        $senders = $config['senders'] ?? [];

        foreach ($senders as $name) {
            $config = $this->getConfig($name);

            if (is_null($config)) {
                throw new InvalidArgumentException("Sms sender [{$name}] is not defined.");
            }

            $drivers[] = $this->createSender($config);
        }

        return new RoundRobinSender($drivers);
    }

    /**
     * Create an instance of the Log Sender driver.
     *
     * @param  array<string, mixed>  $config
     */
    protected function createLogSender(array $config): LogSender
    {
        $logger = $this->app->make(LoggerInterface::class);

        if ($logger instanceof LogManager) {
            $logger = $logger->channel(
                $config['channel'] ?? $this->app['config']->get('sms.log_channel') // @phpstan-ignore-line
            );
        }

        return new LogSender($logger);
    }

    /**
     * Create an instance of the Array Sender Driver.
     */
    protected function createArraySender(): ArraySender
    {
        return new ArraySender;
    }

    /**
     * Set a global address on the sender by type.
     *
     * @param  array<string, mixed>  $config
     */
    protected function setGlobalAddress(Sender $sender, array $config, string $type): void
    {
        $value = Arr::get($config, $type) ?? $this->app['config']['sms.'.$type]; // @phpstan-ignore-line

        if (isset($value)) {
            $sender->{'always'.Str::studly($type)}($value);
        }
    }

    /**
     * Get the sms connection configuration.
     *
     * @return array<string, mixed>|null
     */
    protected function getConfig(string $name): ?array
    {
        return $this->app['config']["sms.senders.{$name}"] ?? null; // @phpstan-ignore-line
    }

    /**
     * Get the default mail driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->app['config']['sms.default']; // @phpstan-ignore-line
    }

    /**
     * Set the default sms driver name.
     */
    public function setDefaultDriver(string $name): void
    {
        $this->app['config']['sms.default'] = $name; // @phpstan-ignore-line
    }

    /**
     * Disconnect the given sender and remove from local cache.
     */
    public function purge(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultDriver();

        unset($this->senders[$name]);
    }

    /**
     * Register a custom transport creator Closure.
     *
     * @return $this
     */
    public function extend(string $driver, Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get the application instance used by the manager.
     */
    public function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @return $this
     */
    public function setApplication(Application $app): self
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Forget all of the resolved mailer instances.
     *
     * @return $this
     */
    public function forgetSenders(): self
    {
        $this->senders = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  array<mixed, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->sender()->$method(...$parameters);
    }
}
