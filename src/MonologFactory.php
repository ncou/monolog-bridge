<?php

declare(strict_types=1);

namespace Chiron\Monolog;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface;
use Throwable;
use Chiron\Container\Container;
use Chiron\Monolog\Config\MonologConfig;

//https://laravel.com/docs/7.x/logging

//https://github.com/laravel/framework/blob/6121a522c1830542f0b1783847894e48d7187c54/src/Illuminate/Log/LogManager.php

// AUTRE EXEMPLE DE FACTORY
//https://github.com/orasik/monolog-middleware/blob/master/src/MonologMiddleware/Extension/MonologConfigurationExtension.php#L104

// TODO : Renommer cette classe en MonologFacory ou LoggerFactory au choix. Et créer une classe de manager mais qui correspond à un de ces deux exemples : https://github.com/merorafael/yii2-monolog/blob/master/src/Mero/Monolog/MonologComponent.php (pour avoir des méthodes du type hasLogger ou close/openChannel) ou alors cette exemple avec une recherche dans le container pour récupérer le channel correspondant :     https://github.com/contributte/monolog/blob/7202b4f785c512fd983c9ccaf0204da1fa5e3638/src/LoggerManager.php.       Dans le principe il faudrait que la classe Manager prenne en paramétre un objet de type Factory qui prendait en paramétre un LoggingConfig et un FactoryInterface.    =>   new LoggerManager(new MonologFactory(LoggingConfig $config, FactoryInterface $factory)).  ca permettra de charger à la volée les channels une fois que l'utilisateur essaye de le récupérer via la méthode getChannel. Mais on pourrait aussi construire tous les channels dans un tableau et le passer en constructeur de la classe LoggerManager(array $channels).
final class MonologFactory
{
    use ParsesLogConfiguration;

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    //protected $app;

    /**
     * The array of resolved channels.
     *
     * @var array
     */
    private $channels = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    // TODO : à virer ????
    private $customCreators = [];

    // TODO : solution temporaire faire mieux que ca en terme de code !!!!
    private $conf = [];

    private $container;

    //private $storagePath;

    /**
     * Create a new Log manager instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    // TODO : remplacer le container par un FactoryInterface !!!!
    public function __construct(Container $container, MonologConfig $config)
    {
        //$this->app = $app;
        $this->container = $container;
        $this->conf = $config->getData();
        $this->storagePath = directory('@runtime/logs/chiron.log');
    }

    // TODO : méthode temporaire et à améliorer !!!!!
    // la valeur de retour est un tableau de type ['channel_name' => Psr\LoggerInterface]
    public function getAllChannels(): array
    {

        // TODO : lever une exception si la valeur du champ défault n'est pas un "channel" qui existe dans le tableau "channels" !!!!

        $result['default'] = $this->get($this->getDefaultDriver());

        foreach ($this->conf['channels'] as $channel => $options) {
            $result[$channel] = $this->get($channel);
        }

        return $result;

    }


    /**
     * Create a new, on-demand aggregate logger instance.
     *
     * @param array       $channels
     * @param string|null $channel
     *
     * @return \Psr\Log\LoggerInterface
     */
    // TODO : à virer
    public function stack(array $channels, ?string $channel = null): LoggerInterface
    {
        return $this->createStackDriver(compact('channels', 'channel'));
    }

    /**
     * Get a log channel instance.
     *
     * @param string|null $channel
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function channel(?string $channel = null): LoggerInterface
    {
        return $this->driver($channel);
    }

    /**
     * Get a log driver instance.
     *
     * @param string|null $driver
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function driver(?string $driver = null): LoggerInterface
    {
        return $this->get($driver ?? $this->getDefaultDriver());
    }

    /**
     * Attempt to get the log from the local cache.
     *
     * @param string $name
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function get(string $name): LoggerInterface
    {
        try {
            //return $this->channels[$name] ?? $this->channels[$name] = $this->resolve($name);

            if (isset($this->channels[$name])) {
                return $this->channels[$name];
            }

            return $this->channels[$name] = $this->resolve($name);
        } catch (Throwable $e) {
            $logger = $this->createEmergencyLogger();
            $logger->emergency('Unable to create configured logger. Using emergency logger.', ['exception' => $e]);


            // TODO : c'est un test, code à virer !!!!
            throw $e;


        }
    }

    /**
     * Create an emergency log handler to avoid white screens of death.
     *
     * @return \Psr\Log\LoggerInterface
     */
    // TODO : voir si cette méthode doit être conservée !!!!!! il faudrait surement placer la config emergency dans le fichier de config et mettre cette balise en required !!!
    private function createEmergencyLogger(): LoggerInterface
    {
        //TODO : virer le storage_path et utiliser : "php://stderr" non ????
        return new Monolog('chiron', $this->prepareHandlers([new StreamHandler(
            $this->storagePath . '/logs/chiron.log',
            $this->level(['level' => 'debug'])
        )]));
    }

    /**
     * Resolve the given log instance by name.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function resolve(string $name): LoggerInterface
    {
        $config = $this->configurationFor($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Log [{$name}] is not defined.");
        }
        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }
        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';
        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param array $config
     *
     * @return mixed
     */
    private function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($config);
    }

    /**
     * Create a custom log driver instance.
     *
     * @param array $config
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function createCustomDriver(array $config): LoggerInterface
    {
        $factory = is_callable($via = $config['via']) ? $via : $this->container->build($via);

        return $factory($config);
    }

    /**
     * Create an aggregate log driver instance.
     *
     * @param array $config
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function createStackDriver(array $config): LoggerInterface
    {
        /*
        $handlers = collect($config['channels'])->flatMap(function ($channel) {
            return $this->channel($channel)->getHandlers();
        })->all();*/

        $handlers = [];
        foreach ($config['channels'] as $channel) {
            //$handlers[] = $this->channel($channel)->getHandlers();
            $handlers = array_merge($handlers, $this->channel($channel)->getHandlers());
        }

        if ($config['ignore_exceptions'] ?? false) {
            $handlers = [new WhatFailureGroupHandler($handlers)];
        }

        return new Monolog($this->parseChannel($config), $handlers);
    }

    /**
     * Create an instance of the single file log driver.
     *
     * @param array $config
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function createSingleDriver(array $config): LoggerInterface
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(
                new StreamHandler(
                    $config['path'],
                    $this->level($config),
                    $config['bubble'] ?? true,
                    $config['permission'] ?? null,
                    $config['locking'] ?? false
                ),
                $config
            ),
        ]);
    }

    /**
     * Create an instance of the daily file log driver.
     *
     * @param array $config
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function createDailyDriver(array $config): LoggerInterface
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(new RotatingFileHandler(
                $config['path'],
                $config['days'] ?? 7,
                $this->level($config),
                $config['bubble'] ?? true,
                $config['permission'] ?? null,
                $config['locking'] ?? false
            ), $config),
        ]);
    }

    /**
     * Create an instance of the Slack log driver.
     *
     * @param array $config
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function createSlackDriver(array $config): LoggerInterface
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(new SlackWebhookHandler(
                $config['url'],
                $config['channel'] ?? null,
                $config['username'] ?? 'Chiron Log',
                $config['attachment'] ?? true,
                $config['emoji'] ?? ':boom:',
                $config['short'] ?? false,
                $config['context'] ?? true,
                $this->level($config),
                $config['bubble'] ?? true,
                $config['exclude_fields'] ?? []
            ), $config),
        ]);
    }

    /**
     * Create an instance of the syslog log driver.
     *
     * @param array $config
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function createSyslogDriver(array $config): LoggerInterface
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(new SyslogHandler(
                'chiron',
                //Str::snake($this->conf['config']['app.name'], '-'),
                $config['facility'] ?? LOG_USER,
                $this->level($config)
            ), $config),
        ]);
    }

    /**
     * Create an instance of the "error log" log driver.
     *
     * @param array $config
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function createErrorlogDriver(array $config): LoggerInterface
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(new ErrorLogHandler(
                $config['type'] ?? ErrorLogHandler::OPERATING_SYSTEM,
                $this->level($config)
            )),
        ]);
    }

    /**
     * Create an instance of any handler available in Monolog.
     *
     * @param array $config
     *
     * @throws \InvalidArgumentException
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function createMonologDriver(array $config): LoggerInterface
    {
        if (! is_a($config['handler'], HandlerInterface::class, true)) {
            throw new InvalidArgumentException(
                $config['handler'] . ' must be an instance of ' . HandlerInterface::class
            );
        }
        $with = array_merge(
            ['level' => $this->level($config)],
            $config['handler_with'] ?? []
        );

        return new Monolog($this->parseChannel($config), [$this->prepareHandler(
            $this->container->build($config['handler'], $with),
            $config
        )]);
    }

    /**
     * Prepare the handlers for usage by Monolog.
     *
     * @param array $handlers
     *
     * @return array
     */
    private function prepareHandlers(array $handlers): array
    {
        foreach ($handlers as $key => $handler) {
            $handlers[$key] = $this->prepareHandler($handler);
        }

        return $handlers;
    }

    /**
     * Prepare the handler for usage by Monolog.
     *
     * @param \Monolog\Handler\HandlerInterface $handler
     * @param array                             $config
     *
     * @return \Monolog\Handler\HandlerInterface
     */
    private function prepareHandler(HandlerInterface $handler, array $config = []): HandlerInterface
    {
        if (! isset($config['formatter'])) {
            $handler->setFormatter($this->formatter());
        } elseif ($config['formatter'] !== 'default') {
            $handler->setFormatter($this->container->build($config['formatter'], $config['formatter_with'] ?? []));
        }

        return $handler;
    }

    /**
     * Get a Monolog formatter instance.
     *
     * @return \Monolog\Formatter\FormatterInterface
     */
    private function formatter(): FormatterInterface
    {
        $formatter = new LineFormatter(null, null, true, true);
        $formatter->includeStacktraces();

        return $formatter;
    }

    /**
     * Get the log connection configuration.
     *
     * @param string $name
     *
     * @return array
     */
    private function configurationFor(string $name): array
    {
        return $this->conf['channels'][$name];
    }

    /**
     * Get the default log driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->conf['default'];
    }

    /**
     * Set the default log driver name.
     *
     * @param string $name
     */
    public function setDefaultDriver(string $name): void
    {
        $this->conf['default'] = $name;
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string   $driver
     * @param \Closure $callback
     *
     * @return $this
     */
    // TODO : méthode à virer ????
    public function extend(string $driver, Closure $callback): self
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * Get fallback log channel name.
     *
     * @return string
     */
    private function getFallbackChannelName(): string
    {
        //return $this->app->bound('env') ? $this->app->environment() : 'production';
        // TODO : améliorer le code !!!!!!
        return 'chironTEMP';
    }
}
