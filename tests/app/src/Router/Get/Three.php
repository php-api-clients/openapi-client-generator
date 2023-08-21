<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Router\Get;

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
final class Three
{
    private array $router = array();
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Hydrators $hydrators, private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication)
    {
    }
    /**
     * @return iterable<Schema\Cat>|iterable<string>|(Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish)
     */
    public function call(string $call, array $params, array $pathChunks) : iterable|\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish
    {
        $matched = false;
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($pathChunks[2] == 'gatos') {
                    if ($call == 'GET /pets/gatos') {
                        $matched = true;
                        if (\array_key_exists(Router\Get\PetsList::class, $this->router) == false) {
                            $this->router[Router\Get\PetsList::class] = new Router\Get\PetsList($this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators, $this->browser, $this->authentication);
                        }
                        return $this->router[Router\Get\PetsList::class]->Gatos($params);
                    }
                } elseif ($pathChunks[2] == 'names') {
                    if ($call == 'GET /pets/names') {
                        $matched = true;
                        if (\array_key_exists(Router\Get\Pets::class, $this->router) == false) {
                            $this->router[Router\Get\Pets::class] = new Router\Get\Pets($this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators, $this->browser, $this->authentication);
                        }
                        return $this->router[Router\Get\Pets::class]->Names($params);
                    }
                } elseif ($pathChunks[2] == '{petId}') {
                    if ($call == 'GET /pets/{petId}') {
                        $matched = true;
                        if (\array_key_exists(Router\Get::class, $this->router) == false) {
                            $this->router[Router\Get::class] = new Router\Get($this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators, $this->browser, $this->authentication);
                        }
                        return $this->router[Router\Get::class]->ShowPetById($params);
                    }
                }
            }
        }
        if ($matched === false) {
            throw new \InvalidArgumentException();
        }
    }
}
