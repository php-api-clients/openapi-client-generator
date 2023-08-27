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
final class Two
{
    public function __construct(private \ApiClients\Client\PetStore\Internal\Routers $routers)
    {
    }
    /**
     * @return iterable<Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish>
     */
    public function call(string $call, array $params, array $pathChunks) : iterable
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($call == 'GET /pets') {
                    return $this->routers->internal🔀Router🔀Get🔀Pets()->list($params);
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
