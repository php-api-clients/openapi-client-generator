<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class Path
{
    public function __construct(
        public readonly string $className,
        public readonly Hydrator $hydrator,
        /** @var array<Operation> */
        public readonly array $operations,
    ) {
    }
}
