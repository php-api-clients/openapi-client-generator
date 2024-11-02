<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class GreenEyes implements \ApiClients\Client\PetStore\Contract\GreenEyes
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
                "hulk",
                "forest",
                "feral"
            ],
            "type": "string"
        }
    }
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "type": "hulk"
}';
    public function __construct(public int $count, public string $type)
    {
    }
}
