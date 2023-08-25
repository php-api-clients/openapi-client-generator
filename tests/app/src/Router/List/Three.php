<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Router\List;

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
     * @return iterable<Schema\Cat>|iterable<string>
     */
    public function call(string $call, array $params, array $pathChunks) : iterable
    {
        if ($pathChunks[0] == '') {
            if ($pathChunks[1] == 'pets') {
                if ($pathChunks[2] == 'gatos') {
                    if ($call == 'LIST /pets/gatos') {
                        if (\array_key_exists(Router\List\PetsList::class, $this->router) == false) {
                            $this->router[Router\List\PetsList::class] = new Router\List\PetsList($this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators, $this->browser, $this->authentication);
                        }
                        return $this->router[Router\List\PetsList::class]->GatosListing($params);
                    }
                } elseif ($pathChunks[2] == 'names') {
                    if ($call == 'LIST /pets/names') {
                        if (\array_key_exists(Router\List\Pets::class, $this->router) == false) {
                            $this->router[Router\List\Pets::class] = new Router\List\Pets($this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators, $this->browser, $this->authentication);
                        }
                        return $this->router[Router\List\Pets::class]->NamesListing($params);
                    }
                }
            }
        }
        throw new \InvalidArgumentException();
    }
}
