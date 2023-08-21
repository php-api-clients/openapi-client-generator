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
     * @return ($call is Operation\Pets\List_::OPERATION_MATCH ? iterable<(Schema\Cat | Schema\Dog | Schema\Bird | Schema\Fish)> : ($call is Operation\Pets\ListListing::OPERATION_MATCH ? iterable<(Schema\Cat | Schema\Dog | Schema\Bird | Schema\Fish)> : ($call is Operation\Pets\Create::OPERATION_MATCH ? array{code: int} : ($call is Operation\Pets\List_\Gatos::OPERATION_MATCH ? iterable<Schema\Cat> : ($call is Operation\Pets\List_\GatosListing::OPERATION_MATCH ? iterable<Schema\Cat> : ($call is Operation\Pets\Names::OPERATION_MATCH ? iterable<string> : ($call is Operation\Pets\NamesListing::OPERATION_MATCH ? iterable<string> : (Schema\Cat | Schema\Dog | Schema\Bird | Schema\Fish)))))))))
     */
    // phpcs:enable
    public function call(string $call, array $params = array()) : iterable|\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish;
    public function operations() : OperationsInterface;
}
