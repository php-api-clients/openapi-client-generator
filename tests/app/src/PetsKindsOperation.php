<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

final class PetsKindsOperation
{
    public function __construct(public \ApiClients\Client\PetStore\Internal\Operators $operators)
    {
    }
    /**
     * @return iterable<int,\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function walking(int $perPage, int $page): \Rx\Observable|\ApiClients\Client\PetStore\Schema\Error
    {
        return $this->operators->pets👷Kinds👷Walking()->call($perPage, $page);
    }
}
