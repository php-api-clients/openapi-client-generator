<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Parameter;
use cebe\openapi\spec\Operation as openAPIOperation;
use cebe\openapi\spec\PathItem;
use Jawira\CaseConverter\Convert;
use Psr\Http\Message\ResponseInterface;

final class Hydrator
{
    public static function gather(
        string $className,
        \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema ...$schemaClasses,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator {
        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator(
            $className,
            str_replace(['\\', '/'], ['/', '🌀'], lcfirst($className)),
            $schemaClasses,
        );
    }
}
