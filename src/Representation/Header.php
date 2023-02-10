<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use cebe\openapi\spec\Schema as baseSchema;

final class Header
{
    public function __construct(
        public readonly string $name,
        public readonly Schema $schema,
    ){
    }
}
