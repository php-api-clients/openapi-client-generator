<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operation;

final class PetsGroupedBy
{
    public function __construct(public \ApiClients\Client\PetStore\Internal\Operators $operators)
    {
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Error
     */
    public function type(int $perPage, int $page): \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Error
    {
        return $this->operators->pets👷Grouped👷By👷Type()->call($perPage, $page);
    }
}
