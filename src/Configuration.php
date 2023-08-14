<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Destination;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\EntryPoints;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Schemas;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\State;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Templates;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Voter;
use ApiClients\Tools\OpenApiClientGenerator\Contract\ContentType;
use EventSauce\ObjectHydrator\MapFrom;

final readonly class Configuration
{
    /** @param array<class-string<ContentType>>|null $contentType */
    public function __construct(
        public State $state,
        public string $spec,
        #[MapFrom('entryPoints')]
        public EntryPoints $entryPoints,
        public Templates|null $templates,
        public Namespace_ $namespace,
        public Destination $destination,
        #[MapFrom('contentType')]
        public array|null $contentType,
        #[MapFrom('subSplit')]
        public SubSplit|null $subSplit,
        public Schemas|null $schemas,
        public Voter|null $voter,
    ) {
    }
}
