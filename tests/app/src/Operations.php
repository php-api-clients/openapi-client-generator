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
final readonly class Operations implements OperationsInterface
{
    public function __construct(private \ApiClients\Client\PetStore\Operators $operators)
    {
    }
    public function pets() : Operation\Pets
    {
        return new Operation\Pets($this->operators);
    }
    public function petsList() : Operation\PetsList
    {
        return new Operation\PetsList($this->operators);
    }
    /**
     * @return Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish
     */
    public function showPetById() : \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|array
    {
        return $this->operators->showPetById()->call();
    }
}
