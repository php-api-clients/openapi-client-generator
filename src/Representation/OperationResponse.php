<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use cebe\openapi\spec\Schema as baseSchema;

final class OperationResponse
{
    public function __construct(
        public readonly int $code,
        public readonly string $contentType,
        public readonly string $description,
        public readonly Schema $schema,
    ){
    }
}
