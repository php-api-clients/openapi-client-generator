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
final readonly class HellHound implements Contract\HellHound
{
    public const SCHEMA_JSON = '{
    "required": [
        "id",
        "name",
        "bad-boy"
    ],
    "type": "object",
    "properties": {
        "id": {
            "type": "integer",
            "format": "int64"
        },
        "name": {
            "type": "string"
        },
        "bad-boy": {
            "type": "bool"
        },
        "eyes": {
            "type": "object",
            "allOf": [
                {
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
                }
            ]
        }
    }
}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '{
    "id": 2,
    "name": "generated",
    "bad-boy": false,
    "eyes": []
}';
    public function __construct(public int $id, public string $name, #[\EventSauce\ObjectHydrator\MapFrom('bad-boy')] public bool $badMinBoy, public ?Schema\HellHound\Eyes $eyes)
    {
    }
}
