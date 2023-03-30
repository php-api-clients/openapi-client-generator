<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Voter;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Schemas;

final readonly class Configuration
{
    public function __construct(
        public string $spec,
        public string $namespace,
        public string $destination,
        public ?Schemas $schemas,
        public ?Voter $voter,
    ) {
    }
}
