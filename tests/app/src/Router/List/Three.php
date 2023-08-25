<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Router\List;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Hydrator;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Operator;
use ApiClients\Client\PetStore\Schema;
use ApiClients\Client\PetStore\WebHook;
use ApiClients\Client\PetStore\Router;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Three
{
    public function __construct(private \ApiClients\Client\PetStore\Routers $routers)
    {
    }
    /**
     * @return iterable<Schema\Cat>|iterable<string>
     */
    public function call(string $call, array $params, array $pathChunks) : iterable
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($pathChunks[2] == 'gatos') {
                    if ($call == 'LIST /pets/gatos') {
                        return $this->routers->router🔀List🔀PetsList()->gatosListing($params);
                    }
                } elseif ($pathChunks[2] == 'names') {
                    if ($call == 'LIST /pets/names') {
                        return $this->routers->router🔀List🔀Pets()->namesListing($params);
                    }
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
