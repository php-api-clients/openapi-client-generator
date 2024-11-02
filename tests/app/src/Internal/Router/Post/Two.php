<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Post;

final readonly class Two
{
    public function __construct(private \ApiClients\Client\PetStore\Internal\Routers $routers)
    {
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
     */
    public function call(string $call, array $params, array $pathChunks): ApiClients\Client\PetStore\Schema\Error|ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($call === 'POST /pets') {
                    return $this->routers->internal🔀Router🔀Post🔀Pets()->create($params);
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
