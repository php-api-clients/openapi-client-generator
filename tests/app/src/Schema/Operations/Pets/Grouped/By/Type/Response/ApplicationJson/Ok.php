<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final readonly class Ok implements Contract\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok
{
    public const SCHEMA_JSON = '{
    "required": [
        "pets"
    ],
    "type": "object",
    "properties": {
        "pets": {
            "type": "array",
            "items": {
                "type": "object",
                "oneOf": [
                    {
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
                    },
                    {
                        "required": [
                            "id",
                            "name",
                            "good-boy",
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
                            "good-boy": {
                                "type": "bool"
                            },
                            "eyes": {
                                "maxItems": 2,
                                "minItems": 2,
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
                    },
                    {
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
                    }
                ]
            }
        }
    }
}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '{
    "pets": [
        {
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
        },
        {
            "id": "4ccda740-74c3-4cfa-8571-ebf83c8f300a",
            "name": "generated",
            "good-boy": false,
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
        }
    ]
}';
    /**
     * @param array<\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound> $pets
     */
    public function __construct(#[\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Multiple\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets] public array $pets)
    {
    }
}
