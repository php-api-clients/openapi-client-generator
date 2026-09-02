<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class Fins implements \ApiClients\Client\PetStore\Contract\Fins
{
    const string SCHEMA_JSON = '{
    "required": [
        "count",
        "spikes"
    ],
    "type": "object",
    "properties": {
        "count": {
            "type": "integer"
        },
        "spikes": {
            "type": "integer"
        }
    }
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "count": 5,
    "spikes": 6
}';
    public function __construct(public int $count, public int $spikes)
    {
    }
}

