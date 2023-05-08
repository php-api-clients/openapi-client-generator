<?php

declare(strict_types=1);

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

    public static function webHook(WebHook ...$webHooks): string|false
    {
        return 'webhook';
    }
}
