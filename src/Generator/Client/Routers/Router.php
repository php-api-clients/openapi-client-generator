<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers;

final readonly class Router
{
    public function __construct(
        public string $class,
        public string $method,
        public string $loopUpMethod,
    ) {
    }
}
