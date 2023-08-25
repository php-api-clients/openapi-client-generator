<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Router\Post;

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
final class Two
{
    public function __construct(private \ApiClients\Client\PetStore\Routers $routers)
    {
    }
    /**
     * @return array{code: int}
     */
    public function call(string $call, array $params, array $pathChunks) : array
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($call == 'POST /pets') {
                    return $this->routers->router🔀Post🔀Pets()->create($params);
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
