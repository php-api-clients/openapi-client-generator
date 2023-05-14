<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class WebHook
{
    /**
     * @param array<Header>         $headers
     * @param array<string, Schema> $schema
     */
    public function __construct(
        public readonly string $event,
        public readonly string $summary,
        public readonly string $description,
        public readonly string $operationId,
        public readonly string $documentationUrl,
        /** @var array<Header> */
        public readonly array $headers,
        /** @var array<string, Schema> */
        public readonly array $schema,
    ) {
    }
}
