<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operator\Pets;

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
final readonly class Create
{
    public const OPERATION_ID = 'pets/create';
    public const OPERATION_MATCH = 'POST /pets';
    private const METHOD = 'POST';
    private const PATH = '/pets';
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private Hydrator\Operation\Pets $hydrator)
    {
    }
    /**
     * @return array{code:int}
     */
    public function call(array $params) : array
    {
        $operation = new \ApiClients\Client\PetStore\Operation\Pets\Create($this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrator);
        $request = $operation->createRequest($params);
        $result = \React\Async\await($this->browser->request($request->getMethod(), (string) $request->getUri(), $request->withHeader('Authorization', $this->authentication->authHeader())->getHeaders(), (string) $request->getBody())->then(function (\Psr\Http\Message\ResponseInterface $response) use($operation) : array {
            return $operation->createResponse($response);
        }));
        if ($result instanceof \Rx\Observable) {
            $result = \WyriHaximus\React\awaitObservable($result);
        }
        return $result;
    }
}
