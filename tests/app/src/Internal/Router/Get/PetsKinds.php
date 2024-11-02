<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Router\Get;

final class PetsKinds
{
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrators $hydrators, private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication)
    {
    }
    /**
     * @return iterable<int,\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function walking(array $params): Rx\Observable|ApiClients\Client\PetStore\Schema\Error
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
        $operator = new \ApiClients\Client\PetStore\Internal\Operator\Pets\Kinds\Walking($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Kinds🌀Walking());
        return $operator->call($arguments['per_page'], $arguments['page']);
    }
}
