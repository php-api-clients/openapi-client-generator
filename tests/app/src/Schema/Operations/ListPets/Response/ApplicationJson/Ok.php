<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema\Operations\ListPets\Response\ApplicationJson;

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
final readonly class Ok
{
    public const SCHEMA_JSON = '{"anyOf":[{"required":["id","name","eyes"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string"},"indoor":{"type":"bool"},"features":{"type":"object"},"eyes":{"type":"object","oneOf":[{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["blood","wine","stale"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["sky","boobies"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["hulk","forest","feral"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["snake"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["rage"],"type":"string"}}}]}}},{"required":["id","name","eyes"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string"},"good-boy":{"type":"bool"},"eyes":{"type":"object","oneOf":[{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["blood","wine","stale"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["sky","boobies"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["hulk","forest","feral"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["snake"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["rage"],"type":"string"}}}]}}},{"required":["id","name","eyes"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string4"},"flies":{"type":"bool"},"eyes":{"type":"object","oneOf":[{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["blood","wine","stale"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["sky","boobies"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["hulk","forest","feral"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["snake"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["rage"],"type":"string"}}}]}}},{"required":["id","name","eyes"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string"},"flat":{"type":"bool"},"eyes":{"type":"object","oneOf":[{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["blood","wine","stale"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["sky","boobies"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["hulk","forest","feral"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["snake"],"type":"string"}}},{"required":["count","type"],"type":"object","properties":{"count":{"type":"integer"},"type":{"enum":["rage"],"type":"string"}}}]}}}]}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '[]';
    public function __construct()
    {
    }
}
