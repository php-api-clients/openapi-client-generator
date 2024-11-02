<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class Fish implements \ApiClients\Client\PetStore\Contract\Fish
{
    const string SCHEMA_JSON = '{
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
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "id": "4ccda740-74c3-4cfa-8571-ebf83c8f300a",
    "name": "generated",
    "flat": false,
    "flies": false,
    "eyes": {
        "count": 5,
        "type": "rage"
    }
}';
    public function __construct(
        public string $id,
        public string $name,
        public bool $flat,
        public bool $flies,
        #[\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Fish\Eyes]
        public \ApiClients\Client\PetStore\Schema\RedEyes|\ApiClients\Client\PetStore\Schema\BlueEyes|\ApiClients\Client\PetStore\Schema\GreenEyes|\ApiClients\Client\PetStore\Schema\YellowEyes|\ApiClients\Client\PetStore\Schema\BlackEyes $eyes
    )
    {
    }
}
