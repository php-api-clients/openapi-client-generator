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
final class ListListing
{
    public const OPERATION_ID = 'pets/list';
    public const OPERATION_MATCH = 'LIST /pets';
    /**The number of results per page (max 100). **/
    private int $perPage;
    /**Page number of the results to fetch. **/
    private int $page;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator;
    private readonly Internal\Hydrator\Operation\Pets $hydrator;
    public function __construct(\League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, Internal\Hydrator\Operation\Pets $hydrator, int $perPage = 30, int $page = 1)
    {
        $this->perPage = $perPage;
        $this->page = $page;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest() : \Psr\Http\Message\RequestInterface
    {
        return new \RingCentral\Psr7\Request('GET', (string) (new \League\Uri\UriTemplate('/pets{?page,per_page}'))->expand(array('page' => $this->page, 'per_page' => $this->perPage)));
    }
    /**
     * @return \Rx\Observable<Schema\Cat|Schema\Dog|Schema\HellHound|Schema\Bird|Schema\Fish|Schema\Spider>
     */
    public function createResponse(\Psr\Http\Message\ResponseInterface $response) : \Rx\Observable
    {
        $code = $response->getStatusCode();
        [$contentType] = explode(';', $response->getHeaderLine('Content-Type'));
        switch ($contentType) {
            case 'application/json':
                $body = json_decode($response->getBody()->getContents(), true);
                switch ($code) {
                    /**
                     * A paged array of pets
                     **/
                    case 200:
                        return \Rx\Observable::fromArray($body, new \Rx\Scheduler\ImmediateScheduler())->map(function (array $body) : Schema\Cat|Schema\Dog|Schema\HellHound|Schema\Bird|Schema\Fish|Schema\Spider {
                            $error = new \RuntimeException();
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Cat::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrator->hydrateObject(Schema\Cat::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaaa;
                            }
                            items_application_json_two_hundred_aaaaa:
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Dog::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrator->hydrateObject(Schema\Dog::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaab;
                            }
                            items_application_json_two_hundred_aaaab:
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\HellHound::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrator->hydrateObject(Schema\HellHound::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaac;
                            }
                            items_application_json_two_hundred_aaaac:
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Bird::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrator->hydrateObject(Schema\Bird::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaad;
                            }
                            items_application_json_two_hundred_aaaad:
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Fish::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrator->hydrateObject(Schema\Fish::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaae;
                            }
                            items_application_json_two_hundred_aaaae:
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Spider::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrator->hydrateObject(Schema\Spider::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaaf;
                            }
                            items_application_json_two_hundred_aaaaf:
                            throw $error;
                        });
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
