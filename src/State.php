<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\State\Files as StateFiles;
use EventSauce\ObjectHydrator\MapFrom;

final class State
{
    public function __construct(
        #[MapFrom('specHash')]
        public string $specHash,
        #[MapFrom('generatedFiles')]
        public StateFiles $generatedFiles,
        #[MapFrom('additionalFiles')]
        public StateFiles $additionalFiles,
    ) {
    }
}
