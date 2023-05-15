<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class Parameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $targetName,
        public readonly string $description,
        public readonly string $type,
        public readonly ?string $format,
        public readonly string $location,
        public readonly mixed $default,
        public readonly ExampleData $example,
    ) {
    }
}
