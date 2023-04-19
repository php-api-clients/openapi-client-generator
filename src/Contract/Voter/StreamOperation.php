<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Contract\Voter;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;

interface StreamOperation
{
    public static function stream(Operation $operation): bool;
}
