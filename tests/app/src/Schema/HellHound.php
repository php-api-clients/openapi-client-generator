<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema;

final readonly class HellHound implements \ApiClients\Client\PetStore\Contract\HellHound
{
    const string SCHEMA_JSON = '{
    "required": [
        "id",
        "name",
        "bad-boy"
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
        "bad-boy": {
            "type": "bool"
        },
        "eyes": {
            "type": "object",
            "allOf": [
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
                }
            ]
        }
    }
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '{
    "id": 2,
    "name": "generated",
    "bad-boy": false,
    "eyes": []
}';
    public function __construct(
        public int $id,
        public string $name,
        #[\EventSauce\ObjectHydrator\MapFrom('bad-boy')]
        public bool $badMinBoy,
        public ?\ApiClients\Client\PetStore\Schema\HellHound\Eyes $eyes
    )
    {
    }
}
