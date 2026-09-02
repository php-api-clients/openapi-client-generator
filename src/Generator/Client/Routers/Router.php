<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers;

use OpenAPITools\Utils\ClassString;

final readonly class Router
{
    public function __construct(
        public ClassString $class,
        public string $method,
        public string $loopUpMethod,
    ) {
    }
}
