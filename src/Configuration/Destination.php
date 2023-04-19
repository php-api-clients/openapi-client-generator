<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

final readonly class Destination
{
    public function __construct(
        public string $root,
        public string $source,
        public string $test,
    ) {
    }
}
