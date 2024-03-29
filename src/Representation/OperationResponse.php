<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class OperationResponse
{
    public function __construct(
        public readonly int|string $code,
        public readonly string $contentType,
        public readonly string $description,
        public readonly Schema|PropertyType $content,
    ) {
    }
}
