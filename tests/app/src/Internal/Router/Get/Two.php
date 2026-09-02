<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Get;

final readonly class Two
{
    public function __construct(private \ApiClients\Client\PetStore\Internal\Routers $routers)
    {
    }
    /**
     * @return iterable<int,\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function call(string $call, array $params, array $pathChunks): Rx\Observable|ApiClients\Client\PetStore\Schema\Error
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($call === 'GET /pets') {
                    return $this->routers->internal🔀Router🔀Get🔀Pets()->list($params);
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
