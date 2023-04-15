<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Methods;

use PhpParser\Node;

final readonly class ChunkCount
{
    /**
     * @param array<Node> $nodes
     */
    public function __construct(
        public string $className,
        public array $nodes,
    ) {
    }
}
