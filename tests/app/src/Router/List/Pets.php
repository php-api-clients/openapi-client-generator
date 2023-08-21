<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Router\List;

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
    public function listListing(array $params) : iterable
    {
        $matched = true;
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
        if (\array_key_exists(Hydrator\Operation\Pets::class, $this->hydrator) == false) {
            $this->hydrator[Hydrator\Operation\Pets::class] = $this->hydrators->getObjectMapperOperation🌀Pets();
        }
        $arguments['page'] = 1;
        do {
            $operator = new Operator\Pets\ListListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrator[Hydrator\Operation\Pets::class]);
            $items = $operator->call($arguments['per_page'], $arguments['page']);
            yield from $items;
            $arguments['page']++;
        } while (count($items) > 0);
    }
    /**
     * @return iterable<string>
     */
    public function namesListing(array $params) : iterable
    {
        $matched = true;
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
        if (\array_key_exists(Hydrator\Operation\Pets\Names::class, $this->hydrator) == false) {
            $this->hydrator[Hydrator\Operation\Pets\Names::class] = $this->hydrators->getObjectMapperOperation🌀Pets🌀Names();
        }
        $arguments['page'] = 1;
        do {
            $operator = new Operator\Pets\NamesListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrator[Hydrator\Operation\Pets\Names::class]);
            $items = $operator->call($arguments['per_page'], $arguments['page']);
            yield from $items;
            $arguments['page']++;
        } while (count($items) > 0);
    }
}
