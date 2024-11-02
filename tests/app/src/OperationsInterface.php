<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

interface OperationsInterface
{
    public function pets(): Operation\Pets;
    public function petsList(): Operation\PetsList;
    public function petsKinds(): Operation\PetsKinds;
    public function petsGroupedBy(): Operation\PetsGroupedBy;
    /**
     * @return \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider|\ApiClients\Client\PetStore\Schema\Error
     */
    public function showPetById(): \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider|\ApiClients\Client\PetStore\Schema\Error;
}
