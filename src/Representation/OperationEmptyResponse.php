<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final readonly class OperationEmptyResponse
{
    /** @param array<Header> $headers */
    public function __construct(
        public int $code,
        public string $description,
        /** @var array<Header> $headers */
        public array $headers,
    ) {
    }
}
