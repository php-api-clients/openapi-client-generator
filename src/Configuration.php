<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Destination;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Voter;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Schemas;
use EventSauce\ObjectHydrator\MapFrom;

final readonly class Configuration
{
    public function __construct(
        public string $spec,
        public string $namespace,
        public Destination $destination,
        #[MapFrom('subSplit')]
        public ?SubSplit $subSplit,
        public ?Schemas $schemas,
        public ?Voter $voter,
    ) {
    }
}
