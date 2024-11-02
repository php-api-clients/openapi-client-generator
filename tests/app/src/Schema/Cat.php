<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class Cat implements \ApiClients\Client\PetStore\Contract\Cat
{
    const string SCHEMA_JSON = '{
    "required": [
        "id",
        "name",
        "indoor",
        "features",
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
        "indoor": {
            "type": "bool"
        },
        "features": {
            "type": "object"
        },
        "eyes": {
            "maxItems": 2,
            "minItems": 2,
            "type": "array",
            "items": {
                "type": "object",
                "anyOf": [
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
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "id": "4ccda740-74c3-4cfa-8571-ebf83c8f300a",
    "name": "generated",
    "indoor": false,
    "features": [],
    "eyes": [
        {
            "count": 5,
            "type": "blood"
        },
        {
            "count": 5,
            "type": "sky"
        }
    ]
}';
    /**
     * @param array<\ApiClients\Client\PetStore\Schema\RedEyes|\ApiClients\Client\PetStore\Schema\BlueEyes|\ApiClients\Client\PetStore\Schema\GreenEyes|\ApiClients\Client\PetStore\Schema\YellowEyes|\ApiClients\Client\PetStore\Schema\BlackEyes> $eyes
     */
    public function __construct(
        public string $id,
        public string $name,
        public bool $indoor,
        public \ApiClients\Client\PetStore\Schema\Cat\Features $features,
        #[\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Multiple\Schema\Cat\Eyes]
        public array $eyes
    )
    {
    }
}
