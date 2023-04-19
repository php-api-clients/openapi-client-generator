<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\State;

final readonly class File
{
    public function __construct(
        public string $name,
        public string $hash,
    ) {
    }
}
