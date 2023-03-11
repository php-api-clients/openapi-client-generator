<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Contract\Voter;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;

interface StreamOperation
{
    public static function stream(Operation $operation): bool;
}
