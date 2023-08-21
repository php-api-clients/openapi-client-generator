<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operation;

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
final class PetsList
{
    private array $operator = array();
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Hydrators $hydrators)
    {
    }
    public function gatos(int $perPage, int $page) : Schema\Cat
    {
        if (\array_key_exists(Operator\Pets\List_\Gatos::class, $this->operator) == false) {
            $this->operator[Operator\Pets\List_\Gatos::class] = new Operator\Pets\List_\Gatos($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Gatos());
        }
        return $this->operator[Operator\Pets\List_\Gatos::class]->call($perPage, $page);
    }
    public function gatosListing(int $perPage, int $page) : Schema\Cat
    {
        if (\array_key_exists(Operator\Pets\List_\GatosListing::class, $this->operator) == false) {
            $this->operator[Operator\Pets\List_\GatosListing::class] = new Operator\Pets\List_\GatosListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Gatos());
        }
        return $this->operator[Operator\Pets\List_\GatosListing::class]->call($perPage, $page);
    }
}
