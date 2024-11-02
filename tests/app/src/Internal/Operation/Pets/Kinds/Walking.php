<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operation\Pets\Kinds;

final class Walking
{
    public const OPERATION_ID = 'pets/kinds/walking';
    public const OPERATION_MATCH = 'GET /pets/kinds/walking';
    /**The number of results per page (max 100). **/
    private int $perPage;
    /**Page number of the results to fetch. **/
    private int $page;
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Kinds\Walking $hydrator, int $perPage = 30, int $page = 1)
    {
        $this->perPage = $perPage;
        $this->page = $page;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest(): \Psr\Http\Message\RequestInterface
    {
        return new \React\Http\Message\Request('GET', (string) (new \League\Uri\UriTemplate('/pets/kinds/walking{?page,per_page}'))->expand(['page' => $this->page, 'per_page' => $this->perPage]));
    }
    /**
     * @return \Rx\Observable<\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound>|\ApiClients\Client\PetStore\Schema\Error
     */
    public function createResponse(\Psr\Http\Message\ResponseInterface $response): \Rx\Observable|\ApiClients\Client\PetStore\Schema\Error
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
                        return \Rx\Observable::fromArray($body, new \Rx\Scheduler\ImmediateScheduler())->map(function (array $body): \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound {
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
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\HellHound::SCHEMA_JSON, '\cebe\openapi\spec\Schema'));
                                return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\HellHound::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_2;
                            }
                            items_application_json_two_hundred_2:
                            throw $error;
                        });
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
