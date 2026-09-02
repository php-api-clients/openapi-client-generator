<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operator\Pets;

final readonly class Create
{
    public const OPERATION_ID = 'pets/create';
    public const OPERATION_MATCH = 'POST /pets';
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets $hydrator)
    {
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
     */
    public function call(array $params): \ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
    {
        $operation = new \ApiClients\Client\PetStore\Internal\Operation\Pets\Create($this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrator);
        $request = $operation->createRequest($params);
        $result = \React\Async\await($this->browser->request($request->getMethod(), (string) $request->getUri(), $request->withHeader('Authorization', $this->authentication->authHeader())->getHeaders(), (string) $request->getBody())->then(function (\Psr\Http\Message\ResponseInterface $response) use ($operation): \ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody {
            return $operation->createResponse($response);
        }));
        if ($result instanceof \Rx\Observable) {
            $result = \WyriHaximus\React\awaitObservable($result);
        }
        return $result;
    }
}
