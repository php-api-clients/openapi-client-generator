<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Operation\Pets\List_;

final class Gatos
{
    public const OPERATION_ID = 'pets/list/gatos';
    public const OPERATION_MATCH = 'GET /pets/gatos';
    /**The number of results per page (max 100). **/
    private int $perPage;
    /**Page number of the results to fetch. **/
    private int $page;
    public function __construct(private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Gatos $hydrator, int $perPage = 30, int $page = 1)
    {
        $this->perPage = $perPage;
        $this->page = $page;
        $this->responseSchemaValidator = $responseSchemaValidator;
        $this->hydrator = $hydrator;
    }
    public function createRequest(): \Psr\Http\Message\RequestInterface
    {
        return new \React\Http\Message\Request('GET', (string) (new \League\Uri\UriTemplate('/pets/gatos{?page,per_page}'))->expand(['page' => $this->page, 'per_page' => $this->perPage]));
    }
    /**
     * @return \Rx\Observable<\ApiClients\Client\PetStore\Schema\Cat>|\ApiClients\Client\PetStore\Schema\Error
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
                        return \Rx\Observable::fromArray($body, new \Rx\Scheduler\ImmediateScheduler())->map(function (array $body): \ApiClients\Client\PetStore\Schema\Cat {
                            $error = new \RuntimeException();
                            try {
                                $this->responseSchemaValidator->validate($body, \cebe\openapi\Reader::readFromJson(\ApiClients\Client\PetStore\Schema\Cat::SCHEMA_JSON, '\cebe\openapi\spec\Schema'));
                                return $this->hydrator->hydrateObject(\ApiClients\Client\PetStore\Schema\Cat::class, $body);
                            } catch (\Throwable $error) {
                                goto items_application_json_two_hundred_0;
                            }
                            items_application_json_two_hundred_0:
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
