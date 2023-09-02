<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\List;

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
     * @return iterable<int,Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish>
     */
    public function listListing(array $params) : iterable
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
        $arguments['page'] = 1;
        do {
            $operator = new Internal\Operator\Pets\ListListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
            $items = [...$operator->call($arguments['per_page'], $arguments['page'])];
            yield from $items;
            $arguments['page']++;
        } while (count($items) > 0);
    }
    /**
     * @return iterable<int,string>
     */
    public function namesListing(array $params) : iterable
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
        $arguments['page'] = 1;
        do {
            $operator = new Internal\Operator\Pets\NamesListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Names());
            $items = [...$operator->call($arguments['per_page'], $arguments['page'])];
            yield from $items;
            $arguments['page']++;
        } while (count($items) > 0);
    }
}
