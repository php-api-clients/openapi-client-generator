<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Get;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Pets
{
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private Internal\Hydrators $hydrators, private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication)
    {
    }
    /**
     * @return iterable<Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish>
     */
    public function list(array $params) : iterable
    {
        $arguments = array();
        if (array_key_exists('per_page', $params) === false) {
            throw new \InvalidArgumentException('Missing mandatory field: per_page');
        }
        $arguments['per_page'] = $params['per_page'];
        unset($params['per_page']);
        if (array_key_exists('page', $params) === false) {
            throw new \InvalidArgumentException('Missing mandatory field: page');
        }
        $arguments['page'] = $params['page'];
        unset($params['page']);
        $operator = new Internal\Operator\Pets\List_($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        return $operator->call($arguments['per_page'], $arguments['page']);
    }
    /**
     * @return iterable<string>
     */
    public function names(array $params) : iterable
    {
        $arguments = array();
        if (array_key_exists('per_page', $params) === false) {
            throw new \InvalidArgumentException('Missing mandatory field: per_page');
        }
        $arguments['per_page'] = $params['per_page'];
        unset($params['per_page']);
        if (array_key_exists('page', $params) === false) {
            throw new \InvalidArgumentException('Missing mandatory field: page');
        }
        $arguments['page'] = $params['page'];
        unset($params['page']);
        $operator = new Internal\Operator\Pets\Names($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Names());
        return $operator->call($arguments['per_page'], $arguments['page']);
    }
}
