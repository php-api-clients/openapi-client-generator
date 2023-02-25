<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class PropertyType
{
    public function __construct(
        public readonly string $type,
        public readonly string|Schema $payload,
    ){
    }
}
