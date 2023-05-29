<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

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
final readonly class Dog
{
    public const SCHEMA_JSON = '{"required":["id","name"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string"},"good-boy":{"type":"bool"}}}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '{"id":2,"name":"generated","good-boy":false}';
    public function __construct(public int $id, public string $name, #[\EventSauce\ObjectHydrator\MapFrom('good-boy')] public ?bool $goodMinBoy)
    {
    }
}
