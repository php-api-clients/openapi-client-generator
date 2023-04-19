<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class Header
{
    public function __construct(
        public readonly string $name,
        public readonly Schema $schema,
    ) {
    }
}
