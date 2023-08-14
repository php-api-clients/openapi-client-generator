<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Hydrator;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Operator;
use ApiClients\Client\PetStore\Schema;
use ApiClients\Client\PetStore\WebHook;
use ApiClients\Client\PetStore\Router;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
interface ClientInterface
{
    // phpcs:disable
    /**
     * @return ($call is Operation\Pets\List_::OPERATION_MATCH ? Operations\Pets\List_\Response\ApplicationJson\Ok : ($call is Operation\Pets\Create::OPERATION_MATCH ? \Psr\Http\Message\ResponseInterface : Operations\ShowPetById\Response\ApplicationJson\Ok)))
     */
    // phpcs:enable
    public function call(string $call, array $params = array());
    public function operations() : OperationsInterface;
}
