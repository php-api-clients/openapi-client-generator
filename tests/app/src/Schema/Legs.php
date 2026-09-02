<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class Legs implements \ApiClients\Client\PetStore\Contract\Legs
{
    const string SCHEMA_JSON = '{
    "required": [
        "count",
        "joints"
    ],
    "type": "object",
    "properties": {
        "count": {
            "type": "integer"
        },
        "joins": {
            "type": "integer"
        }
    }
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "joins": 5
}';
    public function __construct(public int $count, public ?int $joins)
    {
    }
}
