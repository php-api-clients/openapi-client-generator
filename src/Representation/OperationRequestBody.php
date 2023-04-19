<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class OperationRequestBody
{
    public function __construct(
        public readonly string $contentType,
        public readonly Schema $schema,
    ) {
    }
}
