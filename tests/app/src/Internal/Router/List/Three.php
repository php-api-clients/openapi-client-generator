<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\List;

use ApiClients\Client\PetStore\Contract;
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
     * @return iterable<int,Schema\Cat>|iterable<int,string>
     */
    public function call(string $call, array $params, array $pathChunks) : iterable
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($pathChunks[2] == 'gatos') {
                    if ($call == 'LIST /pets/gatos') {
                        return $this->routers->internal🔀Router🔀List🔀PetsList()->gatosListing($params);
                    }
                } elseif ($pathChunks[2] == 'names') {
                    if ($call == 'LIST /pets/names') {
                        return $this->routers->internal🔀Router🔀List🔀Pets()->namesListing($params);
                    }
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
