<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

final class PetsListOperation
{
    public function __construct(public \ApiClients\Client\PetStore\Internal\Operators $operators)
    {
    }
    /**
     * @return iterable<int,\ApiClients\Client\PetStore\Schema\Cat>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function gatos(int $perPage, int $page): \Rx\Observable|\ApiClients\Client\PetStore\Schema\Error
    {
        return $this->operators->pets👷List👷Gatos()->call($perPage, $page);
    }
}
