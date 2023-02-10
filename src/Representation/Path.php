<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use cebe\openapi\spec\Schema as baseSchema;

final class Path
{
    public function __construct(
        public readonly string $className,
        public readonly Hydrator $hydrator,
        /** @var array<Operation> */
        public readonly array $operations,
    ){
    }
}
