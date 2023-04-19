<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class Property
{
    public function __construct(
        public readonly string $name,
        public readonly string $sourceName,
        public readonly string $description,
        public readonly mixed $exampleData,
        /** @var array<PropertyType> */
        public readonly array $type,
        public readonly bool $nullable,
    ) {
    }
}
