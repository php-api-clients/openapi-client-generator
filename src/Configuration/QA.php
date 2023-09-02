<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\QA\Tool;

final readonly class QA
{
    public function __construct(
        public Tool|null $phpcs,
        public Tool|null $phpstan,
        public Tool|null $psalm,
    ) {
    }
}
