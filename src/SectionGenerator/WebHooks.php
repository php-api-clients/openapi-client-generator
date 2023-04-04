<?php

namespace ApiClients\Tools\OpenApiClientGenerator\SectionGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Contract\SectionGenerator;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook;

final class WebHooks implements SectionGenerator
{
    public static function path(Path $path): string|false
    {
        return false;
    }

    public static function webhook(WebHook ...$webHooks): string|false
    {
        return 'webhook';
    }
}
