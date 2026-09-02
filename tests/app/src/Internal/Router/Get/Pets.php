<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Get;

final class Pets
{
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrators $hydrators, private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication)
    {
    }
    /**
     * @return iterable<int,\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function list(array $params): Rx\Observable|ApiClients\Client\PetStore\Schema\Error
    {
        $arguments = [];
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
        $operator = new \ApiClients\Client\PetStore\Internal\Operator\Pets\List_($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        return $operator->call($arguments['per_page'], $arguments['page']);
    }
    /**
     * @return iterable<int,string>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function names(array $params): Rx\Observable|ApiClients\Client\PetStore\Schema\Error
    {
        $arguments = [];
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
        $operator = new \ApiClients\Client\PetStore\Internal\Operator\Pets\Names($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Names());
        return $operator->call($arguments['per_page'], $arguments['page']);
    }
}
