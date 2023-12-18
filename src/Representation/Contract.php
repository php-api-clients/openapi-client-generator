<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;

final class Contract
{
    /** @param array<Property> $properties */
    public function __construct(
        public readonly ClassString $className,
        /** @var array<Property> $properties */
        public readonly array $properties,
    ) {
    }
}
