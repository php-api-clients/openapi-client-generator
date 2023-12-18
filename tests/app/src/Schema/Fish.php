<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final readonly class Fish implements Contract\Fish
{
    public const SCHEMA_JSON = '{
    "required": [
        "id",
        "name",
        "flat",
        "flies",
        "eyes"
    ],
    "type": "object",
    "properties": {
        "id": {
            "type": "string",
            "format": "uuid"
        },
        "name": {
            "type": "string"
        },
        "flat": {
            "type": "bool"
        },
        "flies": {
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
    "id": "4ccda740-74c3-4cfa-8571-ebf83c8f300a",
    "name": "generated",
    "flat": false,
    "flies": false,
    "eyes": {
        "count": 5,
        "type": "rage"
    }
}';
    public function __construct(public string $id, public string $name, public bool $flat, public bool $flies, #[\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Fish\Eyes] public Schema\RedEyes|Schema\BlueEyes|Schema\GreenEyes|Schema\YellowEyes|Schema\BlackEyes $eyes)
    {
    }
}
