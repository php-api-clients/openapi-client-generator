<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class PropertyType
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $format,
        public readonly string|Schema|PropertyType $payload,
        public readonly bool $nullable,
    ) {
    }
}
