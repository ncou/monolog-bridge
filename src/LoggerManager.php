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
use Chiron\Container\SingletonInterface;

// TODO : créer une facade pour le LoggerManager ?????
// TODO : renommer toutes les méthodes en enlevant la partie "logger", cad avoir uniqumenet des méthode (add() / get() / has())
final class LoggerManager
{
    /** @var array */
    private $channels = [];

    // TODO : voir si ce constructeur est encore nécessaire !!!! sinon le virer !!!
    public function __construct(array $channels)
    {
        $this->channels = $channels;
    }

    /**
     * Add logger objectfor the given channel.
     *
     * @param string $channel Logger channel
     * @param LoggerInterface $logger Logger object
     *
     * @throws \InvalidArgumentException
     */
    // TODO : renommer cette méthode addLogger en addChannel() !!!!
    public function addLogger(string $channel, LoggerInterface $logger): void
    {
        // TODO : utiliser la méthode hasLogger()
        if (isset($this->channels[$channel])) {
            throw new InvalidArgumentException(sprintf('Logger channel "%s" already exists.', $channel));
        }

        $this->channels[$channel] = $logger;
    }

    /**
     * Checks if the given logger exists.
     *
     * @param string $channel Logger channel
     *
     * @return bool
     */
    // TODO : renommer cette méthode hasLogger en hasChannel() !!!!
    public function hasLogger(string $channel): bool
    {
        return isset($this->channels[$channel]);
    }

    /**
     * Return logger object.
     *
     * @param string $channel Logger channel
     *
     * @return LoggerInterface Logger object
     *
     * @throws \InvalidArgumentException
     */
    // TODO : créer une méthode getDefaultLogger qui utilise par défaut un channel = 'default', et dans ce cas rendre la paramétre $channel de la méthode getLogger obligatoire et surtout sans valeur par défaut.
    // TODO : renommer cette méthode getLogger en getChannel() !!!!
    public function getLogger(string $channel = 'default'): LoggerInterface
    {
        if (! $this->hasLogger($channel)) {
            // TODO : créer un loggernotfoundexception ????
            throw new InvalidArgumentException(sprintf('Logger channel "%s" not found.', $channel));
        }

        return $this->channels[$channel];
    }
}
