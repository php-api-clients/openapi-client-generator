<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operator;

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
final readonly class CreatePets
{
    public const OPERATION_ID = 'createPets';
    public const OPERATION_MATCH = 'POST /pets';
    private const METHOD = 'POST';
    private const PATH = '/pets';
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private Hydrator\Operation\Pets $hydrator)
    {
    }
    /**
     * @return \React\Promise\PromiseInterface<array>
     **/
    public function call(array $params) : \React\Promise\PromiseInterface
    {
        $operation = new \ApiClients\Client\PetStore\Operation\CreatePets($this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrator);
        $request = $operation->createRequest($params);
        return $this->browser->request($request->getMethod(), (string) $request->getUri(), $request->withHeader('Authorization', $this->authentication->authHeader())->getHeaders(), (string) $request->getBody())->then(function (\Psr\Http\Message\ResponseInterface $response) use($operation) : array {
            return $operation->createResponse($response);
        });
    }
}
