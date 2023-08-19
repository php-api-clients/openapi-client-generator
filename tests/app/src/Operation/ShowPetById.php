<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operation;

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
final class ShowPetById
{
    public const OPERATION_ID = 'showPetById';
    public const OPERATION_MATCH = 'GET /pets/{petId}';
    private const METHOD = 'GET';
    private const PATH = '/pets/{petId}';
    /**The id of the pet to retrieve **/
    private string $petId;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator;
    private readonly Hydrator\Operation\Pets\PetId $hydrator;
    public function __construct(\League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, Hydrator\Operation\Pets\PetId $hydrator, string $petId)
    {
        $this->petId = $petId;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest() : \Psr\Http\Message\RequestInterface
    {
        return new \RingCentral\Psr7\Request(self::METHOD, \str_replace(array('{petId}'), array($this->petId), self::PATH));
    }
    /**
     * @return Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish
     */
    public function createResponse(\Psr\Http\Message\ResponseInterface $response) : Schema\Cat|Schema\Dog|Schema\Bird|Schema\Fish
    {
        $code = $response->getStatusCode();
        [$contentType] = explode(';', $response->getHeaderLine('Content-Type'));
        switch ($contentType) {
            case 'application/json':
                $body = json_decode($response->getBody()->getContents(), true);
                switch ($code) {
                    /**
                     * Expected response to a valid request
                     **/
                    case 200:
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
                            $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Bird::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                            return $this->hydrator->hydrateObject(Schema\Bird::class, $body);
                        } catch (\Throwable $error) {
                            goto items_application_json_two_hundred_aaaac;
                        }
                        items_application_json_two_hundred_aaaac:
                        try {
                            $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(Schema\Fish::SCHEMA_JSON, '\\cebe\\openapi\\spec\\Schema'));
                            return $this->hydrator->hydrateObject(Schema\Fish::class, $body);
                        } catch (\Throwable $error) {
                            goto items_application_json_two_hundred_aaaad;
                        }
                        items_application_json_two_hundred_aaaad:
                        throw $error;
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
