<?php

declare(strict_types=1);

namespace Chiron\Monolog;

use InvalidArgumentException;
use Monolog\Logger as Monolog;

trait ParsesLogConfiguration
{
    /**
     * The Log levels.
     *
     * @var array
     */
    protected $levels = [
        'debug'     => Monolog::DEBUG,
        'info'      => Monolog::INFO,
        'notice'    => Monolog::NOTICE,
        'warning'   => Monolog::WARNING,
        'error'     => Monolog::ERROR,
        'critical'  => Monolog::CRITICAL,
        'alert'     => Monolog::ALERT,
        'emergency' => Monolog::EMERGENCY,
    ];

    /**
     * Get fallback log channel name.
     *
     * @return string
     */
    abstract protected function getFallbackChannelName();

    /**
     * Parse the string level into a Monolog constant.
     *
     * @param array $config
     *
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    protected function level(array $config)
    {
        $level = $config['level'] ?? 'debug';
        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }

        throw new InvalidArgumentException('Invalid log level.');
    }

    /**
     * Extract the log channel from the given configuration.
     *
     * @param array $config
     *
     * @return string
     */
    // TODO : virer cette méthode et utiliser directement le nom du channel pour définir le nom du logger ?????
    protected function parseChannel(array $config)
    {
        if (! isset($config['name'])) {
            return $this->getFallbackChannelName();
        }

        return $config['name'];
    }
}
