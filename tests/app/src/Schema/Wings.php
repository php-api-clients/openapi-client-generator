<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final readonly class Wings
{
    public const SCHEMA_JSON = '{
    "required": [
        "count",
        "features"
    ],
    "type": "object",
    "properties": {
        "count": {
            "type": "integer"
        },
        "features": {
            "type": "integer"
        }
    }
}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "features": 8
}';
    public function __construct(public int $count, public int $features)
    {
    }
}
