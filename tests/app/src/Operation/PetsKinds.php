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
final class PetsKinds
{
    public function __construct(private Internal\Operators $operators)
    {
    }
    /**
     * @return iterable<int,Schema\Cat|Schema\Dog|Schema\HellHound>
     */
    public function walking(int $perPage, int $page) : iterable
    {
        return $this->operators->pets👷Kinds👷Walking()->call($perPage, $page);
    }
    /**
     * @return iterable<int,Schema\Cat|Schema\Dog|Schema\HellHound>
     */
    public function walkingListing(int $perPage, int $page) : iterable
    {
        return $this->operators->pets👷Kinds👷WalkingListing()->call($perPage, $page);
    }
}
