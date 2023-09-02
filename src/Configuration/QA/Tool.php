<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration\QA;

use EventSauce\ObjectHydrator\MapFrom;

final readonly class Tool
{
    public function __construct(
        public bool $enabled,
        #[MapFrom('configFilePath')]
        public string|null $configFilePath,
    ) {
    }
}
