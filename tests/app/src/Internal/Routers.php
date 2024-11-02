<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal;

final class Routers
{
    private ?\ApiClients\Client\PetStore\Internal\Router\Get\Pets $internal🔀Router🔀Get🔀Pets = null;
    private ?\ApiClients\Client\PetStore\Internal\Router\Get\PetsList $internal🔀Router🔀Get🔀PetsList = null;
    private ?\ApiClients\Client\PetStore\Internal\Router\Get\PetsGroupedBy $internal🔀Router🔀Get🔀PetsGroupedBy = null;
    private ?\ApiClients\Client\PetStore\Internal\Router\Get $internal🔀Router🔀Get = null;
    private ?\ApiClients\Client\PetStore\Internal\Router\Get\PetsKinds $internal🔀Router🔀Get🔀PetsKinds = null;
    private ?\ApiClients\Client\PetStore\Internal\Router\Post\Pets $internal🔀Router🔀Post🔀Pets = null;
    public function __construct(private readonly \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private readonly \React\Http\Browser $browser, private readonly \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private readonly Hydrators $hydrators)
    {
    }
    public function internal🔀Router🔀Get🔀Pets(): \ApiClients\Client\PetStore\Internal\Router\Get\Pets
    {
        if ($this->internal🔀Router🔀Get🔀Pets instanceof \ApiClients\Client\PetStore\Internal\Router\Get\Pets === false) {
            $this->internal🔀Router🔀Get🔀Pets = new \ApiClients\Client\PetStore\Internal\Router\Get\Pets(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get🔀Pets;
    }
    public function internal🔀Router🔀Get🔀PetsList(): \ApiClients\Client\PetStore\Internal\Router\Get\PetsList
    {
        if ($this->internal🔀Router🔀Get🔀PetsList instanceof \ApiClients\Client\PetStore\Internal\Router\Get\PetsList === false) {
            $this->internal🔀Router🔀Get🔀PetsList = new \ApiClients\Client\PetStore\Internal\Router\Get\PetsList(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get🔀PetsList;
    }
    public function internal🔀Router🔀Get🔀PetsGroupedBy(): \ApiClients\Client\PetStore\Internal\Router\Get\PetsGroupedBy
    {
        if ($this->internal🔀Router🔀Get🔀PetsGroupedBy instanceof \ApiClients\Client\PetStore\Internal\Router\Get\PetsGroupedBy === false) {
            $this->internal🔀Router🔀Get🔀PetsGroupedBy = new \ApiClients\Client\PetStore\Internal\Router\Get\PetsGroupedBy(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get🔀PetsGroupedBy;
    }
    public function internal🔀Router🔀Get(): \ApiClients\Client\PetStore\Internal\Router\Get
    {
        if ($this->internal🔀Router🔀Get instanceof \ApiClients\Client\PetStore\Internal\Router\Get === false) {
            $this->internal🔀Router🔀Get = new \ApiClients\Client\PetStore\Internal\Router\Get(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get;
    }
    public function internal🔀Router🔀Get🔀PetsKinds(): \ApiClients\Client\PetStore\Internal\Router\Get\PetsKinds
    {
        if ($this->internal🔀Router🔀Get🔀PetsKinds instanceof \ApiClients\Client\PetStore\Internal\Router\Get\PetsKinds === false) {
            $this->internal🔀Router🔀Get🔀PetsKinds = new \ApiClients\Client\PetStore\Internal\Router\Get\PetsKinds(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get🔀PetsKinds;
    }
    public function internal🔀Router🔀Post🔀Pets(): \ApiClients\Client\PetStore\Internal\Router\Post\Pets
    {
        if ($this->internal🔀Router🔀Post🔀Pets instanceof \ApiClients\Client\PetStore\Internal\Router\Post\Pets === false) {
            $this->internal🔀Router🔀Post🔀Pets = new \ApiClients\Client\PetStore\Internal\Router\Post\Pets(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Post🔀Pets;
    }
}
