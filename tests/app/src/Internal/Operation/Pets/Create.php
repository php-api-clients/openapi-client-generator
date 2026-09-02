<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operation\Pets;

final class Create
{
    public const OPERATION_ID = 'pets/create';
    public const OPERATION_MATCH = 'POST /pets';
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets $hydrator)
    {
        $this->requestSchemaValidator = $requestSchemaValidator;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest(array $data): \Psr\Http\Message\RequestInterface
    {
        $this->requestSchemaValidator->validate($data, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson::SCHEMA_JSON, \cebe\openapi\spec\Schema::class));
        return new \React\Http\Message\Request('POST', (string) (new \League\Uri\UriTemplate('/pets'))->expand([]), ['Content-Type' => 'application/json'], json_encode($data));
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
     */
    public function createResponse(\Psr\Http\Message\ResponseInterface $response): \ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
    {
        $code = $response->getStatusCode();
        [$contentType] = explode(';', $response->getHeaderLine('Content-Type'));
        switch ($contentType) {
            case 'application/json':
                $body = json_decode($response->getBody()->getContents(), true);
                switch ($code) {
                    /**
                     * unexpected error
                     **/
                    default:
                        $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Error::SCHEMA_JSON, \cebe\openapi\spec\Schema::class));
                        return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\Error::class, $body);
                }
                break;
        }
        switch ($code) {
            /**
             * Null response
             **/
            case 201:
                return new \ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody(201, []);
        }
        throw new \RuntimeException('Unable to find matching response code and content type');
    }
}
