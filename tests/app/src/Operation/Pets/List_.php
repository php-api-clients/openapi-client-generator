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
    /**How many items to return at one time (max 100) **/
    private int $limit;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator;
    private readonly Hydrator\Operation\Pets $hydrator;
    public function __construct(\League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, Hydrator\Operation\Pets $hydrator, int $limit)
    {
        $this->limit = $limit;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest() : \Psr\Http\Message\RequestInterface
    {
        return new \RingCentral\Psr7\Request(self::METHOD, \str_replace(array('{limit}'), array($this->limit), self::PATH . '?limit={limit}'));
    }
    public function createResponse(\Psr\Http\Message\ResponseInterface $response) : void
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
        throw new \RuntimeException('Unable to find matching response code and content type');
    }
}
