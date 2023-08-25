<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

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
final class Routers
{
    private ?Router\Get\Pets $router🔀Get🔀Pets = null;
    private ?Router\Get\PetsList $router🔀Get🔀PetsList = null;
    private ?Router\Get $router🔀Get = null;
    private ?Router\List\Pets $router🔀List🔀Pets = null;
    private ?Router\List\PetsList $router🔀List🔀PetsList = null;
    private ?Router\Post\Pets $router🔀Post🔀Pets = null;
    public function __construct(private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \React\Http\Browser $browser, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private Hydrators $hydrators)
    {
    }
    public function router🔀Get🔀Pets() : Router\Get\Pets
    {
        if ($this->router🔀Get🔀Pets instanceof Router\Get\Pets === false) {
            $this->router🔀Get🔀Pets = new Router\Get\Pets(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->router🔀Get🔀Pets;
    }
    public function router🔀Get🔀PetsList() : Router\Get\PetsList
    {
        if ($this->router🔀Get🔀PetsList instanceof Router\Get\PetsList === false) {
            $this->router🔀Get🔀PetsList = new Router\Get\PetsList(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->router🔀Get🔀PetsList;
    }
    public function router🔀Get() : Router\Get
    {
        if ($this->router🔀Get instanceof Router\Get === false) {
            $this->router🔀Get = new Router\Get(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->router🔀Get;
    }
    public function router🔀List🔀Pets() : Router\List\Pets
    {
        if ($this->router🔀List🔀Pets instanceof Router\List\Pets === false) {
            $this->router🔀List🔀Pets = new Router\List\Pets(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->router🔀List🔀Pets;
    }
    public function router🔀List🔀PetsList() : Router\List\PetsList
    {
        if ($this->router🔀List🔀PetsList instanceof Router\List\PetsList === false) {
            $this->router🔀List🔀PetsList = new Router\List\PetsList(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->router🔀List🔀PetsList;
    }
    public function router🔀Post🔀Pets() : Router\Post\Pets
    {
        if ($this->router🔀Post🔀Pets instanceof Router\Post\Pets === false) {
            $this->router🔀Post🔀Pets = new Router\Post\Pets(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
        }
        return $this->router🔀Post🔀Pets;
    }
}
