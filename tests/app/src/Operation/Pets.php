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
final class Pets
{
    public function __construct(private Internal\Operators $operators)
    {
    }
    /**
     * @return iterable<int,Schema\Cat|Schema\Dog|Schema\HellHound|Schema\Bird|Schema\Fish|Schema\Spider>
     */
    public function list(int $perPage, int $page) : iterable
    {
        return $this->operators->pets👷List_()->call($perPage, $page);
    }
    /**
     * @return iterable<int,Schema\Cat|Schema\Dog|Schema\HellHound|Schema\Bird|Schema\Fish|Schema\Spider>
     */
    public function listListing(int $perPage, int $page) : iterable
    {
        return $this->operators->pets👷ListListing()->call($perPage, $page);
    }
    /**
     * @return \ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
     */
    public function create(array $params) : \ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
    {
        return $this->operators->pets👷Create()->call($params);
    }
    /**
     * @return iterable<int,string>
     */
    public function names(int $perPage, int $page) : iterable
    {
        return $this->operators->pets👷Names()->call($perPage, $page);
    }
    /**
     * @return iterable<int,string>
     */
    public function namesListing(int $perPage, int $page) : iterable
    {
        return $this->operators->pets👷NamesListing()->call($perPage, $page);
    }
}
