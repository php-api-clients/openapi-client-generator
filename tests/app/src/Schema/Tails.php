<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class Tails implements \ApiClients\Client\PetStore\Contract\Tails
{
    const string SCHEMA_JSON = '{
    "required": [
        "count"
    ],
    "type": "object",
    "properties": {
        "count": {
            "type": "integer"
        }
    }
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "count": 5
}';
    public function __construct(public int $count)
    {
    }
}
