<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

final readonly class Namespace_
{
    public function __construct(
        public string $source,
        public string $test,
    ) {
    }
}
