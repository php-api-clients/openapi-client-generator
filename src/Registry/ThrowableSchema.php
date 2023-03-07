<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Registry;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use \cebe\openapi\spec\Schema as openAPISchema;

final class ThrowableSchema
{
    private array $throwables = [];

    public function add(string $class): void
    {
        $this->throwables[] = $class;
    }

    public function has(string $class): bool
    {
        return in_array($class, $this->throwables);
    }
}
