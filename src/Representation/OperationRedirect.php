<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class OperationRedirect
{
    public function __construct(
        public readonly int $code,
        public readonly string $description,
        /** @var array<Header> */
        public readonly array $headers,
    ) {
    }
}
