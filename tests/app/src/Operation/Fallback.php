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
final class Fallback
{
    private array $operator = array();
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Hydrators $hydrators)
    {
    }
    public function listPets(int $limit) : \React\Promise\PromiseInterface
    {
        if (\array_key_exists(Operator\ListPets::class, $this->operator) == false) {
            $this->operator[Operator\ListPets::class] = new Operator\ListPets($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->operator[Operator\ListPets::class]->call($limit);
    }
    public function createPets(array $params) : \React\Promise\PromiseInterface
    {
        if (\array_key_exists(Operator\CreatePets::class, $this->operator) == false) {
            $this->operator[Operator\CreatePets::class] = new Operator\CreatePets($this->browser, $this->authentication, $this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->operator[Operator\CreatePets::class]->call($params);
    }
    public function showPetById(string $petId) : \React\Promise\PromiseInterface
    {
        if (\array_key_exists(Operator\ShowPetById::class, $this->operator) == false) {
            $this->operator[Operator\ShowPetById::class] = new Operator\ShowPetById($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀PetId());
        }
        return $this->operator[Operator\ShowPetById::class]->call($petId);
    }
}
