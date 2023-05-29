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
final readonly class ListPets
{
    public const OPERATION_ID = 'listPets';
    public const OPERATION_MATCH = 'GET /pets';
    private const METHOD = 'GET';
    private const PATH = '/pets';
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private Hydrator\Operation\Pets $hydrator)
    {
    }
    /**
     * @return \React\Promise\PromiseInterface<mixed>
     **/
    public function call(int $limit) : \React\Promise\PromiseInterface
    {
        $operation = new \ApiClients\Client\PetStore\Operation\ListPets($this->responseSchemaValidator, $this->hydrator, $limit);
        $request = $operation->createRequest();
        return $this->browser->request($request->getMethod(), (string) $request->getUri(), $request->withHeader('Authorization', $this->authentication->authHeader())->getHeaders(), (string) $request->getBody())->then(function (\Psr\Http\Message\ResponseInterface $response) use($operation) : mixed {
            return $operation->createResponse($response);
        });
    }
}
