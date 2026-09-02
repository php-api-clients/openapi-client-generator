<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Contract\Voter;

use OpenAPITools\Representation\Namespaced\Operation;

interface StreamOperation
{
    public static function stream(Operation $operation): bool;
}
