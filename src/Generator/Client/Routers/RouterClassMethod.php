<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers;

use PhpParser\Node;

final readonly class RouterClassMethod
{
    /** @param list<Node\Stmt> $nodes */
    public function __construct(
        public string $name,
        public string $returnType,
        public string $docBlockReturnType,
        public array $nodes,
    ) {
    }
}
