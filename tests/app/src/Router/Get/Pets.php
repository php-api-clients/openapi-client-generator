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
final class Pets
{
    /**
     * @var array<class-string, \EventSauce\ObjectHydrator\ObjectMapper>
     */
    private array $hydrator = array();
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Hydrators $hydrators, private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication)
    {
    }
    /**
     * @return iterable<(Schema\Cat | Schema\Dog | Schema\Bird | Schema\Fish)>
     */
    public function list_(array $params) : iterable
    {
        $matched = true;
        $arguments = array();
        if (array_key_exists('limit', $params) === false) {
            throw new \InvalidArgumentException('Missing mandatory field: limit');
        }
        $arguments['limit'] = $params['limit'];
        unset($params['limit']);
        if (\array_key_exists(Hydrator\Operation\Pets::class, $this->hydrator) == false) {
            $this->hydrator[Hydrator\Operation\Pets::class] = $this->hydrators->getObjectMapperOperation🌀Pets();
        }
        $operator = new Operator\Pets\List_($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrator[Hydrator\Operation\Pets::class]);
        return $operator->call($arguments['limit']);
    }
    /**
     * @return iterable<string>
     */
    public function names(array $params) : iterable
    {
        $matched = true;
        $arguments = array();
        if (array_key_exists('limit', $params) === false) {
            throw new \InvalidArgumentException('Missing mandatory field: limit');
        }
        $arguments['limit'] = $params['limit'];
        unset($params['limit']);
        if (\array_key_exists(Hydrator\Operation\Pets\Names::class, $this->hydrator) == false) {
            $this->hydrator[Hydrator\Operation\Pets\Names::class] = $this->hydrators->getObjectMapperOperation🌀Pets🌀Names();
        }
        $operator = new Operator\Pets\Names($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrator[Hydrator\Operation\Pets\Names::class]);
        return $operator->call($arguments['limit']);
    }
}
