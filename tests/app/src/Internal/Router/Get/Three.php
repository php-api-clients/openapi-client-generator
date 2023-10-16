<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Get;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Three
{
    public function __construct(private \ApiClients\Client\PetStore\Internal\Routers $routers)
    {
    }
    /**
     * @return iterable<int,Schema\Cat>|iterable<int,string>|Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish
     */
    public function call(string $call, array $params, array $pathChunks) : iterable|\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($pathChunks[2] == 'gatos') {
                    if ($call == 'GET /pets/gatos') {
                        return $this->routers->internal🔀Router🔀Get🔀PetsList()->gatos($params);
                    }
                } elseif ($pathChunks[2] == 'names') {
                    if ($call == 'GET /pets/names') {
                        return $this->routers->internal🔀Router🔀Get🔀Pets()->names($params);
                    }
                } elseif ($pathChunks[2] == '{petId}') {
                    if ($call == 'GET /pets/{petId}') {
                        return $this->routers->internal🔀Router🔀Get()->showPetById($params);
                    }
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
