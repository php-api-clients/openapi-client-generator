<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;

use function lcfirst;
use function str_replace;

final class Hydrator
{
    public static function gather(
        string $className,
        string $nameSpaceSeperator,
        Schema ...$schemaClasses,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator {
        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator(
            $className,
            str_replace(['\\', '/'], ['/', $nameSpaceSeperator], lcfirst($className)),
            $schemaClasses,
        );
    }
}
