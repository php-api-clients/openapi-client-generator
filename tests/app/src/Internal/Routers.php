<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Routers
{
    private ?Internal\Router\Get\Pets $internal🔀Router🔀Get🔀Pets = null;
    private ?Internal\Router\Get\PetsList $internal🔀Router🔀Get🔀PetsList = null;
    private ?Internal\Router\Get\PetsGroupedBy $internal🔀Router🔀Get🔀PetsGroupedBy = null;
    private ?Internal\Router\Get $internal🔀Router🔀Get = null;
    private ?Internal\Router\Get\PetsKinds $internal🔀Router🔀Get🔀PetsKinds = null;
    private ?Internal\Router\List\Pets $internal🔀Router🔀List🔀Pets = null;
    private ?Internal\Router\List\PetsList $internal🔀Router🔀List🔀PetsList = null;
    private ?Internal\Router\List\PetsKinds $internal🔀Router🔀List🔀PetsKinds = null;
    private ?Internal\Router\Post\Pets $internal🔀Router🔀Post🔀Pets = null;
    public function __construct(private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \React\Http\Browser $browser, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private Internal\Hydrators $hydrators)
    {
    }
    public function internal🔀Router🔀Get🔀Pets() : Internal\Router\Get\Pets
    {
        if ($this->internal🔀Router🔀Get🔀Pets instanceof Internal\Router\Get\Pets === false) {
            $this->internal🔀Router🔀Get🔀Pets = new Internal\Router\Get\Pets(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get🔀Pets;
    }
    public function internal🔀Router🔀Get🔀PetsList() : Internal\Router\Get\PetsList
    {
        if ($this->internal🔀Router🔀Get🔀PetsList instanceof Internal\Router\Get\PetsList === false) {
            $this->internal🔀Router🔀Get🔀PetsList = new Internal\Router\Get\PetsList(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get🔀PetsList;
    }
    public function internal🔀Router🔀Get🔀PetsGroupedBy() : Internal\Router\Get\PetsGroupedBy
    {
        if ($this->internal🔀Router🔀Get🔀PetsGroupedBy instanceof Internal\Router\Get\PetsGroupedBy === false) {
            $this->internal🔀Router🔀Get🔀PetsGroupedBy = new Internal\Router\Get\PetsGroupedBy(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get🔀PetsGroupedBy;
    }
    public function internal🔀Router🔀Get() : Internal\Router\Get
    {
        if ($this->internal🔀Router🔀Get instanceof Internal\Router\Get === false) {
            $this->internal🔀Router🔀Get = new Internal\Router\Get(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get;
    }
    public function internal🔀Router🔀Get🔀PetsKinds() : Internal\Router\Get\PetsKinds
    {
        if ($this->internal🔀Router🔀Get🔀PetsKinds instanceof Internal\Router\Get\PetsKinds === false) {
            $this->internal🔀Router🔀Get🔀PetsKinds = new Internal\Router\Get\PetsKinds(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Get🔀PetsKinds;
    }
    public function internal🔀Router🔀List🔀Pets() : Internal\Router\List\Pets
    {
        if ($this->internal🔀Router🔀List🔀Pets instanceof Internal\Router\List\Pets === false) {
            $this->internal🔀Router🔀List🔀Pets = new Internal\Router\List\Pets(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀List🔀Pets;
    }
    public function internal🔀Router🔀List🔀PetsList() : Internal\Router\List\PetsList
    {
        if ($this->internal🔀Router🔀List🔀PetsList instanceof Internal\Router\List\PetsList === false) {
            $this->internal🔀Router🔀List🔀PetsList = new Internal\Router\List\PetsList(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀List🔀PetsList;
    }
    public function internal🔀Router🔀List🔀PetsKinds() : Internal\Router\List\PetsKinds
    {
        if ($this->internal🔀Router🔀List🔀PetsKinds instanceof Internal\Router\List\PetsKinds === false) {
            $this->internal🔀Router🔀List🔀PetsKinds = new Internal\Router\List\PetsKinds(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀List🔀PetsKinds;
    }
    public function internal🔀Router🔀Post🔀Pets() : Internal\Router\Post\Pets
    {
        if ($this->internal🔀Router🔀Post🔀Pets instanceof Internal\Router\Post\Pets === false) {
            $this->internal🔀Router🔀Post🔀Pets = new Internal\Router\Post\Pets(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->internal🔀Router🔀Post🔀Pets;
    }
}
