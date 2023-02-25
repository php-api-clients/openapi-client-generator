<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use cebe\openapi\spec\Schema as baseSchema;

final class OperationRequestBody
{
    public function __construct(
        public readonly string $contentType,
        public readonly Schema $schema,
    ){
    }
}
