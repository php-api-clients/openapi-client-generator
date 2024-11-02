<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class BlackEyes implements \ApiClients\Client\PetStore\Contract\BlackEyes
{
    const string SCHEMA_JSON = '{
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
                "rage"
            ],
            "type": "string"
        }
    }
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "type": "rage"
}';
    public function __construct(public int $count, public string $type)
    {
    }
}

