<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Get;

final readonly class Four
{
    public function __construct(private \ApiClients\Client\PetStore\Internal\Routers $routers)
    {
    }
    /**
     * @return iterable<int,\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function call(string $call, array $params, array $pathChunks): Rx\Observable|ApiClients\Client\PetStore\Schema\Error
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($pathChunks[2] == 'kinds') {
                    if ($pathChunks[3] == 'walking') {
                        if ($call === 'GET /pets/kinds/walking') {
                            return $this->routers->internal🔀Router🔀Get🔀PetsKinds()->walking($params);
                        }
                    }
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
