<?php

declare(strict_types=1);

namespace Chiron\Monolog\Bootloader;

use Chiron\Core\Directories;
use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Core\Publisher;

final class PublishMonologBootloader extends AbstractBootloader
{
    public function boot(Publisher $publisher, Directories $directories): void
    {
        $publisher->add(__DIR__ . '/../../config/monolog.php.dist', $directories->get('@config/monolog.php'));
    }
}
