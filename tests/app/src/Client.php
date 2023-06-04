<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

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
final class Client implements ClientInterface
{
    private readonly \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication;
    private readonly \React\Http\Browser $browser;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator;
    private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator;
    private array $router = array();
    private readonly OperationsInterface $operations;
    private readonly Hydrators $hydrators;
    public function __construct(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, \React\Http\Browser $browser)
    {
        $this->authentication = $authentication;
        $this->browser = $browser->withBase('http://petstore.swagger.io/v1')->withFollowRedirects(false);
        $this->requestSchemaValidator = new \League\OpenAPIValidation\Schema\SchemaValidator(\League\OpenAPIValidation\Schema\SchemaValidator::VALIDATE_AS_REQUEST);
        $this->responseSchemaValidator = new \League\OpenAPIValidation\Schema\SchemaValidator(\League\OpenAPIValidation\Schema\SchemaValidator::VALIDATE_AS_RESPONSE);
        $this->hydrators = new Hydrators();
        $this->operations = new Operations(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
    }
    // phpcs:disable
    /**
     * @return ($call is Operation\Pets\List_::OPERATION_MATCH ? Operations\Pets\List_\Response\ApplicationJson\Ok : ($call is Operation\Pets\Create::OPERATION_MATCH ? \Psr\Http\Message\ResponseInterface : Operations\ShowPetById\Response\ApplicationJson\Ok)))
     */
    // phpcs:enable
    public function call(string $call, array $params = array())
    {
        [$method, $path] = explode(' ', $call);
        $pathChunks = explode('/', $path);
        $pathChunksCount = count($pathChunks);
        $matched = false;
        if ($method === 'GET') {
            if ($pathChunksCount === 2) {
                $matched = true;
                if (\array_key_exists(Router\Get\Two::class, $this->router) == false) {
                    $this->router[Router\Get\Two::class] = new Router\Get\Two(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
                }
                $this->router[Router\Get\Two::class]->call($call, $params, $pathChunks);
            } elseif ($pathChunksCount === 3) {
                $matched = true;
                if (\array_key_exists(Router\Get\Three::class, $this->router) == false) {
                    $this->router[Router\Get\Three::class] = new Router\Get\Three(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
                }
                $this->router[Router\Get\Three::class]->call($call, $params, $pathChunks);
            }
        } elseif ($method === 'POST') {
            if ($pathChunksCount === 2) {
                $matched = true;
                if (\array_key_exists(Router\Post\Two::class, $this->router) == false) {
                    $this->router[Router\Post\Two::class] = new Router\Post\Two(browser: $this->browser, authentication: $this->authentication, requestSchemaValidator: $this->requestSchemaValidator, responseSchemaValidator: $this->responseSchemaValidator, hydrators: $this->hydrators);
                }
                return $this->router[Router\Post\Two::class]->call($call, $params, $pathChunks);
            }
        }
        if ($matched === false) {
            throw new \InvalidArgumentException();
        }
    }
    public function operations() : OperationsInterface
    {
        return $this->operations;
    }
}
