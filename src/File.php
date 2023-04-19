<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator;

use PhpParser\Node;

final class File
{
    public function __construct(
        public readonly string $pathPrefix,
        public readonly string $fqcn,
        public readonly Node|string $contents,
    ) {
    }
}
