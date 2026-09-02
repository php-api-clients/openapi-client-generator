<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class YellowEyes implements \ApiClients\Client\PetStore\Contract\YellowEyes
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
                "snake"
            ],
            "type": "string"
        }
    }
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "type": "snake"
}';
    public function __construct(public int $count, public string $type)
    {
    }
}
