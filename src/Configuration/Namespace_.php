<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

final readonly class Namespace_ //phpcs:disable
{
    public function __construct(
        public string $source,
        public string $test,
    ) {
    }
}
