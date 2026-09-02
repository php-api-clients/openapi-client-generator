<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class Error implements \ApiClients\Client\PetStore\Contract\Error
{
    const string SCHEMA_JSON = '{
    "required": [
        "code",
        "message"
    ],
    "type": "object",
    "properties": {
        "code": {
            "type": "integer",
            "format": "int32"
        },
        "message": {
            "type": "string"
        }
    }
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "code": 4,
    "message": "generated"
}';
    public function __construct(public int $code, public string $message)
    {
    }
}
