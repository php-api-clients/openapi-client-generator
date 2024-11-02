<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema\HellHound;

final readonly class Eyes implements \ApiClients\Client\PetStore\Contract\HellHound\Eyes
{
    const string SCHEMA_JSON = '{
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
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '[]';
    public function __construct()
    {
    }
}

