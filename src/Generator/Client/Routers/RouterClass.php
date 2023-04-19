<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers;

use PhpParser\Node;

final readonly class RouterClass
{
    /**
     * @param array<string, array<Node>> $methods
     */
    public function __construct(
        public string $method,
        public string $group,
        public array $methods,
    ) {
    }
}
