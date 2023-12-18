<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema\HellHound;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final readonly class Eyes implements Contract\HellHound\Eyes
{
    public const SCHEMA_JSON = '{
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
}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '[]';
    public function __construct()
    {
    }
}
