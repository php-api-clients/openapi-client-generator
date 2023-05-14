<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Registry;

use cebe\openapi\spec\Schema as openAPISchema;

final readonly class UnknownSchema
{
    public function __construct(
        public string $name,
        public string $className,
        public openAPISchema $schema,
    ) {
    }
}
