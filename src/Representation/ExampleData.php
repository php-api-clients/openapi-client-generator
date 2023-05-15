<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use PhpParser\Node;

final class ExampleData
{
    public function __construct(
        public readonly mixed $raw,
        public readonly Node\Expr $node,
    ) {
    }
}
