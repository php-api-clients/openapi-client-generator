<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\SectionGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Contract\SectionGenerator;
use OpenAPITools\Representation;

use function array_pop;
use function explode;
use function implode;

final class OperationIdSlash implements SectionGenerator
{
    public static function path(Representation\Namespaced\Path $path): string|false
    {
        $chunks = explode('/', $path->operations[0]->operationId);
        array_pop($chunks);

        return implode('-', $chunks);
    }

    public static function webHook(Representation\WebHook ...$webHooks): string|false
    {
        return false;
    }
}
