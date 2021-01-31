<?php

declare(strict_types=1);

namespace Chiron\Monolog\Bootloader;

use Chiron\Core\Directories;
use Chiron\Core\Exception\BootException;
use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Container\Container;
use Chiron\Monolog\Config\MonologConfig;
use Chiron\Logger\LogManager;
use Chiron\Monolog\MonologFactory;
use Chiron\Monolog\MonologFactory2;
use Throwable;

final class MonologBootloader extends AbstractBootloader
{
    // TODO : code à améliorer !!!
    public function boot(MonologFactory $factory, LogManager $manager): void
    {
        try {
            $channels = $factory->resolveChannels();
        } catch (Throwable $e) {
            throw new BootException('Unable to create configured logger.', $e->getCode(), $e);
        }

        foreach ($channels as $channel => $logger) {
            $manager->set($channel, $logger);
        }
    }
}
