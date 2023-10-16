<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operation\Pets\List_;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class GatosListing
{
    public const OPERATION_ID = 'pets/list/gatos';
    public const OPERATION_MATCH = 'LIST /pets/gatos';
    /**The number of results per page (max 100). **/
    private int $perPage;
    /**Page number of the results to fetch. **/
    private int $page;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator;
    private readonly Internal\Hydrator\Operation\Pets\Gatos $hydrator;
    public function __construct(\League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, Internal\Hydrator\Operation\Pets\Gatos $hydrator, int $perPage = 30, int $page = 1)
    {
        $this->perPage = $perPage;
        $this->page = $page;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest() : \Psr\Http\Message\RequestInterface
    {
        return new \RingCentral\Psr7\Request('GET', \str_replace(array('{per_page}', '{page}'), array($this->perPage, $this->page), '/pets/gatos' . '?per_page={per_page}&page={page}'));
    }
    /**
     * @return \Rx\Observable<Schema\Cat>
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
                     * A paged array of cats
                     **/
                    case 200:
                        return \Rx\Observable::fromArray($body, new \Rx\Scheduler\ImmediateScheduler())->map(function (array $body) : Schema\Cat {
                            $error = new \RuntimeException();
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Cat::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                                return $this->hydrator->hydrateObject(Schema\Cat::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_aaaaa;
                            }
                            items_application_json_two_hundred_aaaaa:
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
