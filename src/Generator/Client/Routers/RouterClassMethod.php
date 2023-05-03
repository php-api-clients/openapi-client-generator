<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers;

use PhpParser\Node;

final readonly class RouterClassMethod
{
    /**
     * @param array<Node> $nodes
     */
    public function __construct(
        public string $name,
        public array $nodes,
    ) {
    }
}
