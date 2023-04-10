<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

final readonly class Templates
{
    public function __construct(
        public string $dir,
        public ?array $variables,
    ) {
    }
}
