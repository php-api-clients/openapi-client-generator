<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class PropertyType
{
    /** @param string|Schema|PropertyType|array<PropertyType> $payload */
    public function __construct(
        public readonly string $type,
        public readonly string|null $format,
        public readonly string|null $pattern,
        public readonly string|Schema|PropertyType|array $payload,
        public readonly bool $nullable,
    ) {
    }
}
