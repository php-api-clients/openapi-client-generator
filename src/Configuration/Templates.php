<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

final readonly class Templates
{
    /**
     * @param array<string, mixed>|null $variables
     */
    public function __construct(
        public string $dir,
        public ?array $variables,
    ) {
    }
}
