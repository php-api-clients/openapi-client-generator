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
final readonly class Spider implements Contract\Spider
{
    public const SCHEMA_JSON = '{
    "required": [
        "id",
        "name",
        "eyes",
        "legs"
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
        "legs": {
            "maxItems": 8,
            "minItems": 8,
            "type": "array",
            "items": {
                "type": "string"
            }
        },
        "eyes": {
            "maxItems": 8,
            "minItems": 8,
            "type": "array",
            "items": {
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
    }
}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '{
    "id": "4ccda740-74c3-4cfa-8571-ebf83c8f300a",
    "name": "generated",
    "legs": [
        "generated",
        "generated",
        "generated",
        "generated",
        "generated",
        "generated",
        "generated",
        "generated"
    ],
    "eyes": [
        {
            "count": 5,
            "type": "blood"
        },
        {
            "count": 5,
            "type": "sky"
        },
        {
            "count": 5,
            "type": "hulk"
        },
        {
            "count": 5,
            "type": "snake"
        },
        {
            "count": 5,
            "type": "rage"
        },
        {
            "count": 5,
            "type": "blood"
        },
        {
            "count": 5,
            "type": "blood"
        },
        {
            "count": 5,
            "type": "blood"
        }
    ]
}';
    /**
     * @param array<\ApiClients\Client\PetStore\Schema\RedEyes|\ApiClients\Client\PetStore\Schema\BlueEyes|\ApiClients\Client\PetStore\Schema\GreenEyes|\ApiClients\Client\PetStore\Schema\YellowEyes|\ApiClients\Client\PetStore\Schema\BlackEyes> $eyes
     */
    public function __construct(public string $id, public string $name, public array $legs, #[\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Multiple\Schema\Spider\Eyes] public array $eyes)
    {
    }
}
