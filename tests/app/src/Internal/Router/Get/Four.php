<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Get;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Four
{
    public function __construct(private \ApiClients\Client\PetStore\Internal\Routers $routers)
    {
    }
    /**
     * @return iterable<int,Schema\Cat|Schema\Dog|Schema\HellHound>
     */
    public function call(string $call, array $params, array $pathChunks) : iterable
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($pathChunks[2] == 'kinds') {
                    if ($pathChunks[3] == 'walking') {
                        if ($call == 'GET /pets/kinds/walking') {
                            return $this->routers->internal🔀Router🔀Get🔀PetsKinds()->walking($params);
                        }
                    }
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
