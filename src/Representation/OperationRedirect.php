<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use cebe\openapi\spec\Schema as baseSchema;

final class OperationRedirect
{
    public function __construct(
        public readonly int $code,
        public readonly string $description,
        /** @var array<Header> */
        public readonly array $headers,
    ){
    }
}
