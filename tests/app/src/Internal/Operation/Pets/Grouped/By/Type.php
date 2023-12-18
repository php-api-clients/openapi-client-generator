<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operation\Pets\Grouped\By;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Type
{
    public const OPERATION_ID = 'pets/grouped/by/type';
    public const OPERATION_MATCH = 'GET /pets/groupedByType';
    /**The number of results per page (max 100). **/
    private int $perPage;
    /**Page number of the results to fetch. **/
    private int $page;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator;
    private readonly Internal\Hydrator\Operation\Pets\GroupedByType $hydrator;
    public function __construct(\League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, Internal\Hydrator\Operation\Pets\GroupedByType $hydrator, int $perPage = 30, int $page = 1)
    {
        $this->perPage = $perPage;
        $this->page = $page;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest() : \Psr\Http\Message\RequestInterface
    {
        return new \RingCentral\Psr7\Request('GET', (string) (new \League\Uri\UriTemplate('/pets/groupedByType{?page,per_page}'))->expand(array('page' => $this->page, 'per_page' => $this->perPage)));
    }
    /**
     * @return Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok
     */
    public function createResponse(\Psr\Http\Message\ResponseInterface $response) : Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok
    {
        $code = $response->getStatusCode();
        [$contentType] = explode(';', $response->getHeaderLine('Content-Type'));
        switch ($contentType) {
            case 'application/json':
                $body = json_decode($response->getBody()->getContents(), true);
                switch ($code) {
                    /**
                     * A shitty design choice to test a specific situation in the generator
                     **/
                    case 200:
                        $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok::SCHEMA_JSON, \cebe\openapi\spec\Schema::class));
                        return $this->hydrator->hydrateObject(Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok::class, $body);
                    /**
                     * unexpected error
                     **/
                    default:
                        $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Error::SCHEMA_JSON, \cebe\openapi\spec\Schema::class));
                        throw new ErrorSchemas\Error($code, $this->hydrator->hydrateObject(Schema\Error::class, $body));
                }
                break;
        }
        throw new \RuntimeException('Unable to find matching response code and content type');
    }
}
