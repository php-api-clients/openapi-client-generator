<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operation;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class PetsGroupedBy
{
    public function __construct(private Internal\Operators $operators)
    {
    }
    /**
     * @return Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok
     */
    public function type(int $perPage, int $page) : \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok
    {
        return $this->operators->pets👷Grouped👷By👷Type()->call($perPage, $page);
    }
}
