<?php

declare(strict_types=1);

namespace Chiron\Monolog\Bootloader;

use Chiron\Core\Directories;
use Chiron\Bootload\AbstractBootloader;
use Chiron\PublishableCollection;

final class PublishMonologBootloader extends AbstractBootloader
{
    public function boot(PublishableCollection $publishable, Directories $directories): void
    {
        $publishable->add(__DIR__ . '/../../config/monolog.php.dist', $directories->get('@config/monolog.php'));
    }
}
