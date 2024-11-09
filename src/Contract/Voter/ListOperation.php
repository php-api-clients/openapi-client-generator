<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Contract\Voter;

use OpenAPITools\Representation\Namespaced\Operation;

interface ListOperation
{
    public static function incrementorKey(): string;

    public static function incrementorInitialValue(): int;

    /** @return array<string> */
    public static function keys(): array;

    public static function list(Operation $operation): bool;
}
