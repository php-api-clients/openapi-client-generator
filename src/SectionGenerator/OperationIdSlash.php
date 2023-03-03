<?php

namespace ApiClients\Tools\OpenApiClientGenerator\SectionGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Contract\SectionGenerator;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook;

final class OperationIdSlash implements SectionGenerator
{
    public static function path(Path $path): string|false
    {
        $chunks = explode('/', $path->operations[0]->operationId);
        array_pop($chunks);

        return implode('-', $chunks);
    }

    public static function webhook(WebHook ...$webHooks): string|false
    {
        return false;
    }
}
