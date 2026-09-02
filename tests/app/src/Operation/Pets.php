<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operation;

final class Pets
{
    public function __construct(public \ApiClients\Client\PetStore\Internal\Operators $operators)
    {
    }
    /**
     * @return iterable<int,\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function list(int $perPage, int $page): \Rx\Observable|\ApiClients\Client\PetStore\Schema\Error
    {
        return $this->operators->pets👷List_()->call($perPage, $page);
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
     */
    public function create(array $params): \ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
    {
        return $this->operators->pets👷Create()->call($params);
    }
    /**
     * @return iterable<int,string>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function names(int $perPage, int $page): \Rx\Observable|\ApiClients\Client\PetStore\Schema\Error
    {
        return $this->operators->pets👷Names()->call($perPage, $page);
    }
}
