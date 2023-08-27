<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\List;

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
                if ($call == 'LIST /pets') {
                    return $this->routers->internal🔀Router🔀List🔀Pets()->listListing($params);
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
