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
final class Pets
{
    private array $operator = array();
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Hydrators $hydrators)
    {
    }
    public function list(int $limit) : Schema\Operations\Pets\List_\Response\ApplicationJson\Ok
    {
        if (\array_key_exists(Operator\Pets\List_::class, $this->operator) == false) {
            $this->operator[Operator\Pets\List_::class] = new Operator\Pets\List_($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->operator[Operator\Pets\List_::class]->call($limit);
    }
    public function create(array $params) : \Psr\Http\Message\ResponseInterface
    {
        if (\array_key_exists(Operator\Pets\Create::class, $this->operator) == false) {
            $this->operator[Operator\Pets\Create::class] = new Operator\Pets\Create($this->browser, $this->authentication, $this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->operator[Operator\Pets\Create::class]->call($params);
    }
}
