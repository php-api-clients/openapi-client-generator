<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final readonly class RedEyes implements Contract\RedEyes, Contract\RedEyes\A
{
    public const SCHEMA_JSON = '{
    "required": [
        "count",
        "type"
    ],
    "type": "object",
    "allOf": [
        {
            "required": [
                "count"
            ],
            "type": "object",
            "properties": {
                "count": {
                    "type": "integer"
                }
            }
        },
        {
            "type": "object",
            "properties": {
                "type": {
                    "enum": [
                        "blood",
                        "wine",
                        "stale"
                    ],
                    "type": "string"
                }
            }
        }
    ]
}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "type": "blood"
}';
    public function __construct(public int $count, public ?string $type)
    {
    }
}
