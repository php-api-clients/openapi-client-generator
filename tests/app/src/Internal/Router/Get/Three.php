<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Get;

final readonly class Three
{
    public function __construct(private \ApiClients\Client\PetStore\Internal\Routers $routers)
    {
    }
    /**
     * @return iterable<int,\ApiClients\Client\PetStore\Schema\Cat>|\ApiClients\Client\PetStore\Schema\Error|\ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|iterable<int,string>|\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider
     */
    public function call(string $call, array $params, array $pathChunks): Rx\Observable|ApiClients\Client\PetStore\Schema\Error|ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|ApiClients\Client\PetStore\Schema\Cat|ApiClients\Client\PetStore\Schema\Dog|ApiClients\Client\PetStore\Schema\Bird|ApiClients\Client\PetStore\Schema\Fish|ApiClients\Client\PetStore\Schema\Spider
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($pathChunks[2] == 'gatos') {
                    if ($call === 'GET /pets/gatos') {
                        return $this->routers->internal🔀Router🔀Get🔀PetsList()->gatos($params);
                    }
                } elseif ($pathChunks[2] == 'groupedByType') {
                    if ($call === 'GET /pets/groupedByType') {
                        return $this->routers->internal🔀Router🔀Get🔀PetsGroupedBy()->type($params);
                    }
                } elseif ($pathChunks[2] == 'names') {
                    if ($call === 'GET /pets/names') {
                        return $this->routers->internal🔀Router🔀Get🔀Pets()->names($params);
                    }
                } elseif ($pathChunks[2] == '{petId}') {
                    if ($call === 'GET /pets/{petId}') {
                        return $this->routers->internal🔀Router🔀Get()->showPetById($params);
                    }
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
