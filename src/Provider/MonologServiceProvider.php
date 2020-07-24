<?php

declare(strict_types=1);

namespace Chiron\Monolog\Provider;

use Chiron\Bootload\ServiceProvider\ServiceProviderInterface;
use Chiron\Container\BindingInterface;
use Chiron\Container\Container;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Chiron\Monolog\LoggerManager;
use Chiron\Monolog\MonologFactory;


class MonologServiceProvider implements ServiceProviderInterface
{

    public function register(BindingInterface $container): void
    {
        $container->singleton(LoggerManager::class, function (MonologFactory $factory) {
            return new LoggerManager($factory->getAllChannels());
        });

        $container->bind(LoggerInterface::class, function (LoggerManager $manager) {
            return $manager->getLogger();
        });
    }


}
