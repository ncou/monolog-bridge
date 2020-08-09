<?php

namespace Chiron\Monolog\Bootloader;

use Chiron\Boot\Directories;
use Chiron\Bootload\AbstractBootloader;
use Chiron\PublishableCollection;

final class PublishMonologBootloader extends AbstractBootloader
{
    public function boot(PublishableCollection $publishable, Directories $directories): void
    {
        $configPath = __DIR__ . '/../../../config';

        $publishable->add($configPath . '/monolog.php.dist', $directories->get('@config/monolog.php'));
    }
}
