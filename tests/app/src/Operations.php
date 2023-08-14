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
final class Operations implements OperationsInterface
{
    private array $operator = array();
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Hydrators $hydrators)
    {
    }
    public function pets() : Operation\Pets
    {
        return new Operation\Pets($this->browser, $this->authentication, $this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators);
    }
    public function showPetById(string $petId) : Schema\Operations\ShowPetById\Response\ApplicationJson\Ok
    {
        if (\array_key_exists(Operator\ShowPetById::class, $this->operator) == false) {
            $this->operator[Operator\ShowPetById::class] = new Operator\ShowPetById($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀PetId());
        }
        return $this->operator[Operator\ShowPetById::class]->call($petId);
    }
}
