<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operator\Pets\Grouped\By;

final readonly class Type
{
    public const OPERATION_ID = 'pets/grouped/by/type';
    public const OPERATION_MATCH = 'GET /pets/groupedByType';
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\GroupedByType $hydrator)
    {
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Error
     */
    public function call(int $perPage = 30, int $page = 1): \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Error
    {
        $operation = new \ApiClients\Client\PetStore\Internal\Operation\Pets\Grouped\By\Type($this->responseSchemaValidator, $this->hydrator, $perPage, $page);
        $request = $operation->createRequest();
        $result = \React\Async\await($this->browser->request($request->getMethod(), (string) $request->getUri(), $request->withHeader('Authorization', $this->authentication->authHeader())->getHeaders(), (string) $request->getBody())->then(function (\Psr\Http\Message\ResponseInterface $response) use ($operation): \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Error {
            return $operation->createResponse($response);
        }));
        if ($result instanceof \Rx\Observable) {
            $result = \WyriHaximus\React\awaitObservable($result);
        }
        return $result;
    }
}
