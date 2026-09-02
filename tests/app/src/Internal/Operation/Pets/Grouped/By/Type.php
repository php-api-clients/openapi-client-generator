<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operation\Pets\Grouped\By;

final class Type
{
    public const OPERATION_ID = 'pets/grouped/by/type';
    public const OPERATION_MATCH = 'GET /pets/groupedByType';
    /**The number of results per page (max 100). **/
    private int $perPage;
    /**Page number of the results to fetch. **/
    private int $page;
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\GroupedByType $hydrator, int $perPage = 30, int $page = 1)
    {
        $this->perPage = $perPage;
        $this->page = $page;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest(): \Psr\Http\Message\RequestInterface
    {
        return new \React\Http\Message\Request('GET', (string) (new \League\Uri\UriTemplate('/pets/groupedByType{?page,per_page}'))->expand(['page' => $this->page, 'per_page' => $this->perPage]));
    }
    /**
     * @return \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Error
     */
    public function createResponse(\Psr\Http\Message\ResponseInterface $response): \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Error
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
                        $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok::SCHEMA_JSON, \cebe\openapi\spec\Schema::class));
                        return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok::class, $body);
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
