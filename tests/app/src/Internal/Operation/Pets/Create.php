<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operation\Pets;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Create
{
    public const OPERATION_ID = 'pets/create';
    public const OPERATION_MATCH = 'POST /pets';
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator;
    private readonly Internal\Hydrator\Operation\Pets $hydrator;
    public function __construct(\League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, Internal\Hydrator\Operation\Pets $hydrator)
    {
        $this->requestSchemaValidator = $requestSchemaValidator;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest(array $data) : \Psr\Http\Message\RequestInterface
    {
        $this->requestSchemaValidator->validate($data, \cebe\openapi\Reader::readFromJson(Schema\Pets\Create\Request\ApplicationJson::SCHEMA_JSON, \cebe\openapi\spec\Schema::class));
        return new \RingCentral\Psr7\Request('POST', (string) (new \League\Uri\UriTemplate('/pets'))->expand(array()), array('Content-Type' => 'application/json'), json_encode($data));
    }
    /**
     * @return \ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
     */
    public function createResponse(\Psr\Http\Message\ResponseInterface $response) : \ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody
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
                        $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Error::SCHEMA_JSON, \cebe\openapi\spec\Schema::class));
                        throw new ErrorSchemas\Error($code, $this->hydrator->hydrateObject(Schema\Error::class, $body));
                }
                break;
        }
        switch ($code) {
            /**
             * Null response
             **/
            case 201:
                return new \ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody(201, array());
        }
        throw new \RuntimeException('Unable to find matching response code and content type');
    }
}
