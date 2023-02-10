<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class Hydrator
{
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
        /** @var array<Schema> $schemas */
        public readonly array $schemas,
    ){
    }
}
