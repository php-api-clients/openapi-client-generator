<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Contract\Voter;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;

interface ListOperation
{

    public static function incrementorKey(): string;

    public static function incrementorInitialValue(): int;


    /**
     * @return array<string>
     */
    public static function keys(): array;

    public static function list(Operation $operation): bool;
}
