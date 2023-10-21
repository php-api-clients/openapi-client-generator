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
interface OperationsInterface
{
    public function pets() : Operation\Pets;
    public function petsList() : Operation\PetsList;
    public function petsKinds() : Operation\PetsKinds;
    public function petsGroupedBy() : Operation\PetsGroupedBy;
    /**
     * @return Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish|Schema\Spider
     */
    public function showPetById() : \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider;
}
