<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class OperationRedirect
{
    /** @param array<Header> $headers */
    public function __construct(
        public readonly int $code,
        public readonly string $description,
        /** @var array<Header> $headers */
        public readonly array $headers,
    ) {
    }
}
