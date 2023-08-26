<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operator\Pets\List_;

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
final readonly class GatosListing
{
    public const OPERATION_ID = 'pets/list/gatos';
    public const OPERATION_MATCH = 'LIST /pets/gatos';
    public function __construct(private \React\Http\Browser $browser, private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private Hydrator\Operation\Pets\Gatos $hydrator)
    {
    }
    /**
     * @return iterable<Schema\Cat>
     */
    public function call(int $perPage = 30, int $page = 1) : iterable
    {
        $operation = new \ApiClients\Client\PetStore\Operation\Pets\List_\GatosListing($this->responseSchemaValidator, $this->hydrator, $perPage, $page);
        $request = $operation->createRequest();
        $result = \React\Async\await($this->browser->request($request->getMethod(), (string) $request->getUri(), $request->withHeader('Authorization', $this->authentication->authHeader())->getHeaders(), (string) $request->getBody())->then(function (\Psr\Http\Message\ResponseInterface $response) use($operation) : \Rx\Observable|array {
            return $operation->createResponse($response);
        }));
        if ($result instanceof \Rx\Observable) {
            $result = \WyriHaximus\React\awaitObservable($result);
        }
        return $result;
    }
}
