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
final class Fallback
{
    /**
     * @var array<class-string, \EventSauce\ObjectHydrator\ObjectMapper>
     */
    private array $hydrator = array();
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator;
    private readonly \ApiClients\Client\PetStore\Hydrators $hydrators;
    private readonly \React\Http\Browser $browser;
    private readonly \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication;
    public function __construct(\League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, \ApiClients\Client\PetStore\Hydrators $hydrators, \React\Http\Browser $browser, \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication)
    {
        $this->requestSchemaValidator = $requestSchemaValidator;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrators = $hydrators;
        $this->browser = $browser;
        $this->authentication = $authentication;
    }
    public function listPets(array $params)
    {
        $arguments = array();
        if (array_key_exists('limit', $params) === false) {
            throw new \InvalidArgumentException('Missing mandatory field: limit');
        }
        $arguments['limit'] = $params['limit'];
        unset($params['limit']);
        if (\array_key_exists(Hydrator\Operation\Pets::class, $this->hydrator) == false) {
            $this->hydrator[Hydrator\Operation\Pets::class] = $this->hydrators->getObjectMapperOperation🌀Pets();
        }
        $operator = new Operator\ListPets($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrator[Hydrator\Operation\Pets::class]);
        return $operator->call($arguments['limit']);
    }
    public function showPetById(array $params)
    {
        $arguments = array();
        if (array_key_exists('petId', $params) === false) {
            throw new \InvalidArgumentException('Missing mandatory field: petId');
        }
        $arguments['petId'] = $params['petId'];
        unset($params['petId']);
        if (\array_key_exists(Hydrator\Operation\Pets\PetId::class, $this->hydrator) == false) {
            $this->hydrator[Hydrator\Operation\Pets\PetId::class] = $this->hydrators->getObjectMapperOperation🌀Pets🌀PetId();
        }
        $operator = new Operator\ShowPetById($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrator[Hydrator\Operation\Pets\PetId::class]);
        return $operator->call($arguments['petId']);
    }
}
