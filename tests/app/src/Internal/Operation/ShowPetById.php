<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operation;

final class ShowPetById
{
    public const OPERATION_ID = 'showPetById';
    public const OPERATION_MATCH = 'GET /pets/{petId}';
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Petid $hydrator)
    {
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest(): \Psr\Http\Message\RequestInterface
    {
        return new \React\Http\Message\Request('GET', (string) (new \League\Uri\UriTemplate('/pets/{petId}'))->expand([]));
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider|\ApiClients\Client\PetStore\Schema\Error
     */
    public function createResponse(\Psr\Http\Message\ResponseInterface $response): \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider|\ApiClients\Client\PetStore\Schema\Error
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
                            $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Cat::SCHEMA_JSON, '\cebe\openapi\spec\Schema'));
                            return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\Cat::class, $body);
                        } catch (\Throwable $error) {
                            goto items_application_json_two_hundred_0;
                        }
                        items_application_json_two_hundred_0:
                        try {
                            $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Dog::SCHEMA_JSON, '\cebe\openapi\spec\Schema'));
                            return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\Dog::class, $body);
                        } catch (\Throwable $error) {
                            goto items_application_json_two_hundred_1;
                        }
                        items_application_json_two_hundred_1:
                        try {
                            $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Bird::SCHEMA_JSON, '\cebe\openapi\spec\Schema'));
                            return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\Bird::class, $body);
                        } catch (\Throwable $error) {
                            goto items_application_json_two_hundred_2;
                        }
                        items_application_json_two_hundred_2:
                        try {
                            $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Fish::SCHEMA_JSON, '\cebe\openapi\spec\Schema'));
                            return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\Fish::class, $body);
                        } catch (\Throwable $error) {
                            goto items_application_json_two_hundred_3;
                        }
                        items_application_json_two_hundred_3:
                        try {
                            $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Spider::SCHEMA_JSON, '\cebe\openapi\spec\Schema'));
                            return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\Spider::class, $body);
                        } catch (\Throwable $error) {
                            goto items_application_json_two_hundred_4;
                        }
                        items_application_json_two_hundred_4:
                        throw $error;
                    /**
                     * unexpected error
                     **/
                    default:
                        $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Error::SCHEMA_JSON, \cebe\openapi\spec\Schema::class));
                        return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\Error::class, $body);
                }
                break;
        }
        throw new \RuntimeException('Unable to find matching response code and content type');
    }
}
