<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema\Pets\Create\Request;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final readonly class ApplicationJson implements Contract\Pets\Create\Request\ApplicationJson
{
    public const SCHEMA_JSON = '{
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
        },
        {
            "required": [
                "id",
                "name",
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
                    "type": "string4"
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
        },
        {
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
        },
        {
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
        }
    ]
}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '[]';
    public function __construct()
    {
    }
}
