<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\RootPackage;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\SectionPackage;
use ApiClients\Tools\OpenApiClientGenerator\Contract\SectionGenerator;
use EventSauce\ObjectHydrator\MapFrom;

final readonly class SubSplit
{
    /** @param array<class-string<SectionGenerator>>|null $sectionGenerator */
    public function __construct(
        #[MapFrom('subSplitsDestination')]
        public string $subSplitsDestination,
        public string $branch,
        #[MapFrom('targetVersion')]
        public string $targetVersion,
        #[MapFrom('subSplitConfiguration')]
        public string $subSplitConfiguration,
        #[MapFrom('fullName')]
        public string $fullName,
        public string $vendor,
        #[MapFrom('sectionGenerator')]
        public array|null $sectionGenerator,
        #[MapFrom('rootPackage')]
        public RootPackage $rootPackage,
        #[MapFrom('sectionPackage')]
        public SectionPackage $sectionPackage,
    ) {
    }
}
