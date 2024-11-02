<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router;

final class Get
{
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrators $hydrators, private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication)
    {
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider|\ApiClients\Client\PetStore\Schema\Error
     */
    public function showPetById(array $params): ApiClients\Client\PetStore\Schema\Cat|ApiClients\Client\PetStore\Schema\Dog|ApiClients\Client\PetStore\Schema\Bird|ApiClients\Client\PetStore\Schema\Fish|ApiClients\Client\PetStore\Schema\Spider|ApiClients\Client\PetStore\Schema\Error
    {
        $operator = new \ApiClients\Client\PetStore\Internal\Operator\ShowPetById($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Petid());
        return $operator->call();
    }
}
