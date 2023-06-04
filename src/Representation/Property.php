<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class Property
{
    /** @param array<mixed> $enum */
    public function __construct(
        public readonly string $name,
        public readonly string $sourceName,
        public readonly string $description,
        public readonly ExampleData $example,
        public readonly PropertyType $type,
        public readonly bool $nullable,
        public readonly array $enum,
    ) {
    }
}
