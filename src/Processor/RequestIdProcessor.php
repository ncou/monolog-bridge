<?php

declare(strict_types=1);

namespace Chiron\Monolog\Processor;

use Monolog\Processor\ProcessorInterface;
use Chiron\Http\RequestScope;

/**
 * Adds the request unique identifier into records.
 */
// TODO : attention cette classe ne va pas bien fonctionner car on n'a pas la dépendance vers le package chiron/http dans ce package chiron/monolog-bridge. Il faudrait plutot créer déplacer cette classe dans le package http mais on aura le même soucis car il faudra ajouter une dépendance au logger :( trouver une solution propre à cette problématique, mais pour l'instant j'ai pas d'idées... (éventuellement ne pas faire le implements ProcessInterface pour ne pas avoir besoin de la dépendance vers le package monolog mais c'est pas trés propre !!!!
class RequestIdProcessor implements ProcessorInterface
{
    /** @var RequestScope */
    private $requestScope;

    public function __construct(RequestScope $requestScope)
    {
        $this->requestScope = $requestScope;
    }

    public function __invoke(array $record): array
    {
        $record['extra']['request_id'] = $this->getRequestId();

        return $record;
    }

    private function getRequestId(): string
    {
        // TODO : gérer le cas ou l'attribut 'request_id' n'est pas présent dans les attributs de la request
        return $this->requestScope->getRequest()->getAttribute('request_id');
    }
}
