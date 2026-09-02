<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema\Operations\Pets\Kinds\Walking\Response\ApplicationJson;

final readonly class Ok implements \ApiClients\Client\PetStore\Contract\Operations\Pets\Kinds\Walking\Response\ApplicationJson\Ok
{
    const string SCHEMA_JSON = '{
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
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '[]';
    public function __construct()
    {
    }
}
