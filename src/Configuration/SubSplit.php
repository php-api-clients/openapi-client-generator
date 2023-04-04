<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Configuration;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\RootPackage;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\SectionPackage;
use ApiClients\Tools\OpenApiClientGenerator\Contract\SectionGenerator;
use EventSauce\ObjectHydrator\MapFrom;

final readonly class SubSplit
{
    /**
     * @param array<class-string<SectionGenerator>>|null $sectionGenerator
     */
    public function __construct(
        #[MapFrom('templatesDir')]
        public string $templatesDir,
        public string $branch,
        #[MapFrom('subSplitConfiguration')]
        public string $subSplitConfiguration,
        #[MapFrom('fullName')]
        public string $fullName,
        public string $vendor,
        #[MapFrom('sectionGenerator')]
        public ?array $sectionGenerator,
        #[MapFrom('rootPackage')]
        public RootPackage $rootPackage,
        #[MapFrom('sectionPackage')]
        public SectionPackage $sectionPackage,
    ) {
    }
}
