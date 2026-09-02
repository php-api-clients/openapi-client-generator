<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class RedEyes implements \ApiClients\Client\PetStore\Contract\RedEyes, \ApiClients\Client\PetStore\Contract\RedEyes\B
{
    const string SCHEMA_JSON = '{
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
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "type": "blood"
}';
    public function __construct(public int $count, public ?string $type)
    {
    }
}
