<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

use EventSauce\ObjectHydrator\MapFrom;

final readonly class EntryPoints
{
    public function __construct(
        public bool $call,
        #[MapFrom('operations')]
        public bool $operations,
        #[MapFrom('webHooks')]
        public bool $webHooks,
    ) {
    }
}
