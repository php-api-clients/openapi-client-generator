<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

final readonly class Operations implements OperationsInterface
{
    public function __construct(public \ApiClients\Client\PetStore\Internal\Operators $operators)
    {
    }
    public function pets(): Operation\Pets
    {
        return new Operation\Pets($this->operators);
    }
    public function petsList(): Operation\PetsList
    {
        return new Operation\PetsList($this->operators);
    }
    public function petsKinds(): Operation\PetsKinds
    {
        return new Operation\PetsKinds($this->operators);
    }
    public function petsGroupedBy(): Operation\PetsGroupedBy
    {
        return new Operation\PetsGroupedBy($this->operators);
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider|\ApiClients\Client\PetStore\Schema\Error
     */
    public function showPetById(): \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider|\ApiClients\Client\PetStore\Schema\Error
    {
        return $this->operators->showPetById()->call();
    }
}
