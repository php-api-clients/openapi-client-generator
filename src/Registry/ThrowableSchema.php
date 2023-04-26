<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Registry;

use function in_array;

final class ThrowableSchema
{
    /** @var array<string> */
    private array $throwables = [];

    public function add(string $class): void
    {
        $this->throwables[] = $class;
    }

    public function has(string $class): bool
    {
        return in_array($class, $this->throwables, true);
    }
}
