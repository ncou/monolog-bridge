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
final class LoggerManager
{
    /** @var array */
    private $channels = [];

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
    public function addLogger(string $channel, LoggerInterface $logger): void
    {
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
    public function getLogger(string $channel = 'default'): LoggerInterface
    {
        if (! $this->hasLogger($channel)) {
            // TODO : créer un loggernotfoundexception ????
            throw new InvalidArgumentException(sprintf('Logger channel "%s" not found.', $channel));
        }

        return $this->channels[$channel];
    }
}
