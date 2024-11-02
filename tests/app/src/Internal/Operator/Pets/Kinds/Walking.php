<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operator\Pets\Kinds;

final readonly class Walking
{
    public const OPERATION_ID = 'pets/kinds/walking';
    public const OPERATION_MATCH = 'GET /pets/kinds/walking';
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Kinds\Walking $hydrator)
    {
    }
    /**
     * @return iterable<int,\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function call(int $perPage = 30, int $page = 1): \Rx\Observable|\ApiClients\Client\PetStore\Schema\Error
    {
        $operation = new \ApiClients\Client\PetStore\Internal\Operation\Pets\Kinds\Walking($this->responseSchemaValidator, $this->hydrator, $perPage, $page);
        $request = $operation->createRequest();
        $result = \React\Async\await($this->browser->request($request->getMethod(), (string) $request->getUri(), $request->withHeader('Authorization', $this->authentication->authHeader())->getHeaders(), (string) $request->getBody())->then(function (\Psr\Http\Message\ResponseInterface $response) use ($operation): \Rx\Observable|\ApiClients\Client\PetStore\Schema\Error {
            return $operation->createResponse($response);
        }));
        if ($result instanceof \Rx\Observable) {
            $result = \WyriHaximus\React\awaitObservable($result);
        }
        return $result;
    }
}
