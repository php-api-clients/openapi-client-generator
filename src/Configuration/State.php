<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;

final readonly class State
{
    /**
     * @param array<string> $additionalFiles
     */
    public function __construct(
        public string $file,
        #[MapFrom('additionalFiles')]
        #[CastListToType('string')]
        public ?array $additionalFiles,
    ) {
    }
}
