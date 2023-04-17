<?php

namespace ApiClients\Tools\OpenApiClientGenerator\State;

final readonly class File
{
    public function __construct(
        public string $name,
        public string $hash,
    ) {
    }
}
