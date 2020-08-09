<?php

declare(strict_types=1);

namespace Chiron\Monolog\Config;

use Chiron\Config\AbstractInjectableConfig;

use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class MonologConfig extends AbstractInjectableConfig
{
    protected const CONFIG_SECTION_NAME = 'monolog';

    protected function getConfigSchema(): Schema
    {
        // TODO : il faudrait vérifier qu'il n'y a pas de doublons dans le nom des channels !!!! ou alors dans ce cas ils sont fusionné automatiquement lorsqu'on va récupérer le schema ????
        return Expect::structure([
            'default'       => Expect::string(),
            'channels'         => Expect::array(), // TODO : structure à finir de coder !!!! c'est un code temporaire pour permettre d'avancer sur les développements.
        ]);
    }

    public function getDefault(): string
    {
        return $this->get('default');
    }

    public function getChannels(): array
    {
        return $this->get('channels');
    }
}
