<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Hydrator;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Operator;
use ApiClients\Client\PetStore\Schema;
use ApiClients\Client\PetStore\WebHook;
use ApiClients\Client\PetStore\Router;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final readonly class Fish
{
    public const SCHEMA_JSON = '{
    "required": [
        "id",
        "name",
        "eyes"
    ],
    "type": "object",
    "properties": {
        "id": {
            "type": "integer",
            "format": "int64"
        },
        "name": {
            "type": "string"
        },
        "flat": {
            "type": "bool"
        },
        "eyes": {
            "type": "object",
            "oneOf": [
                {
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
                                "blood",
                                "wine",
                                "stale"
                            ],
                            "type": "string"
                        }
                    }
                },
                {
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
                                "sky",
                                "boobies"
                            ],
                            "type": "string"
                        }
                    }
                },
                {
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
                },
                {
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
                },
                {
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
                }
            ]
        }
    }
}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '{
    "id": 2,
    "name": "generated",
    "flat": false,
    "eyes": null
}';
    public function __construct(public int $id, public string $name, public ?bool $flat, #[\ApiClients\Client\PetStore\Attribute\CastUnionToType\Schema\Fish\Eyes] public Schema\RedEyes|Schema\BlueEyes|Schema\GreenEyes|Schema\YellowEyes|Schema\BlackEyes $eyes)
    {
    }
}
