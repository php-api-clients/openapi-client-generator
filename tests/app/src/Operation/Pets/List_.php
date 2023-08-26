<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operation\Pets;

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
final class List_
{
    public const OPERATION_ID = 'pets/list';
    public const OPERATION_MATCH = 'GET /pets';
    private const METHOD = 'GET';
    private const PATH = '/pets';
    /**The number of results per page (max 100). **/
    private int $perPage;
    /**Page number of the results to fetch. **/
    private int $page;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator;
    private readonly Hydrator\Operation\Pets $hydrator;
    public function __construct(\League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, Hydrator\Operation\Pets $hydrator, int $perPage = 30, int $page = 1)
    {
        $this->perPage = $perPage;
        $this->page = $page;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest() : \Psr\Http\Message\RequestInterface
    {
        return new \RingCentral\Psr7\Request(self::METHOD, \str_replace(array('{per_page}', '{page}'), array($this->perPage, $this->page), self::PATH . '?per_page={per_page}&page={page}'));
    }
    /**
     * @return \Rx\Observable<Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish>
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
                        return \Rx\Observable::fromArray($body, new \Rx\Scheduler\ImmediateScheduler())->map(function (array $body) : Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish {
                            $error = new \RuntimeException();
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Cat::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrators->hydrateObject(Schema\Cat::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaaa;
                            }
                            items_application_json_two_hundred_aaaaa:
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Dog::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrators->hydrateObject(Schema\Dog::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaab;
                            }
                            items_application_json_two_hundred_aaaab:
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Bird::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrators->hydrateObject(Schema\Bird::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaac;
                            }
                            items_application_json_two_hundred_aaaac:
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Fish::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrators->hydrateObject(Schema\Fish::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaad;
                            }
                            items_application_json_two_hundred_aaaad:
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
