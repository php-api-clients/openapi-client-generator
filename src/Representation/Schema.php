<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use cebe\openapi\spec\Schema as baseSchema;

final class Schema
{
    public function __construct(
        public readonly string $className,
        public readonly string $title,
        public readonly string $description,
        /** @var array<mixed> */
        public readonly array $example,
        /** @var array<Property> */
        public readonly array $properties,
        public readonly baseSchema $schema,
        public readonly bool $isArray,
    ) {
    }
}
