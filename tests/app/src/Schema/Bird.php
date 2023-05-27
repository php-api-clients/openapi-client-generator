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
final readonly class Bird
{
    public const SCHEMA_JSON = '{"required":["id","name"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string4"},"flies":{"type":"bool"}}}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '{"id":2,"name":null,"flies":false}';
    public function __construct(public int $id, public string4 $name, public ?bool $flies)
    {
    }
}
