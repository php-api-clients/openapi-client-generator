<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers;

final readonly class RouterClass
{
    /** @param array<RouterClassMethod> $methods */
    public function __construct(
        public string $method,
        public string $group,
        public array $methods,
    ) {
    }
}
