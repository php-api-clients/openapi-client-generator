<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Router;

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
final class Get
{
    /**
     * @var array<class-string, \EventSauce\ObjectHydrator\ObjectMapper>
     */
    private array $hydrator = array();
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Hydrators $hydrators, private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication)
    {
    }
    /**
     * @return (Schema\Cat | Schema\Dog | Schema\Bird | Schema\Fish)
     */
    public function showPetById(array $params) : \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|array
    {
        if (\array_key_exists(Hydrator\Operation\Pets\PetId::class, $this->hydrator) == false) {
            $this->hydrator[Hydrator\Operation\Pets\PetId::class] = $this->hydrators->getObjectMapperOperation🌀Pets🌀PetId();
        }
        $operator = new Operator\ShowPetById($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrator[Hydrator\Operation\Pets\PetId::class]);
        return $operator->call();
    }
}
