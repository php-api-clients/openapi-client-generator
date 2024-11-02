<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Post;

final class Pets
{
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrators $hydrators, private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication)
    {
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
     */
    public function create(array $params): ApiClients\Client\PetStore\Schema\Error|ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
    {
        $operator = new \ApiClients\Client\PetStore\Internal\Operator\Pets\Create($this->browser, $this->authentication, $this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        return $operator->call($params);
    }
}
