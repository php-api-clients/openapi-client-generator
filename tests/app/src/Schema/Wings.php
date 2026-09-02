<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class Wings implements \ApiClients\Client\PetStore\Contract\Wings
{
    const string SCHEMA_JSON = '{
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
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "features": 8
}';
    public function __construct(public int $count, public int $features)
    {
    }
}
