<?php

declare(strict_types=1);

namespace Chiron\Monolog\Config;

use Chiron\Config\AbstractInjectableConfig;

use Nette\Schema\Expect;
use Nette\Schema\Schema;

// TODO : faire plutot une liste de handlers et ensuite une liste de loggers, ca serait plus propre !!!! => https://stackoverrun.com/fr/q/8457405

final class MonologConfig extends AbstractInjectableConfig
{
    protected const CONFIG_SECTION_NAME = 'monolog';

    protected function getConfigSchema(): Schema
    {
        // TODO : il faudrait vérifier qu'il n'y a pas de doublons dans le nom des channels !!!! ou alors dans ce cas ils sont fusionné automatiquement lorsqu'on va récupérer le schema ????
        return Expect::structure([
            'channels' => Expect::array()->default(
                [
                    'default' => [
                        'driver' => 'stack',
                        'channels' => ['single'],
                        'ignore_exceptions' => false,
                    ],

                    'single' => [
                        'driver' => 'single',
                        'path' => directory('@runtime/logs/chiron.log'),
                        'level' => 'debug',
                    ],
                ]

            ), // TODO : structure à finir de coder !!!! c'est un code temporaire pour permettre d'avancer sur les développements.
        ]);
    }

    public function getChannels(): array
    {
        return $this->get('channels');
    }
}
