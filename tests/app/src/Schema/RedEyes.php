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
final readonly class RedEyes
{
    public const SCHEMA_JSON = '{
    "required": [
        "count",
        "type"
    ],
    "type": "object",
    "properties": {
        "count": {
            "type": "integer"
        },
        "type": {
            "enum": [
                "blood",
                "wine",
                "stale"
            ],
            "type": "string"
        }
    }
}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "type": "stale"
}';
    public function __construct(public int $count, public string $type)
    {
    }
}
