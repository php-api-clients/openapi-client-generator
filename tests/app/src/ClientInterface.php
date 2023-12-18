<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
interface ClientInterface
{
    // phpcs:disable
    /**
     */
    // phpcs:enabled
    public function call(string $call, array $params = array()) : iterable|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody|\ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider;
    public function operations() : OperationsInterface;
}
