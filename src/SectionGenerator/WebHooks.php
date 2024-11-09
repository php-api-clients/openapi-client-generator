<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\SectionGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Contract\SectionGenerator;
use OpenAPITools\Representation;

final class WebHooks implements SectionGenerator
{
    public static function path(Representation\Namespaced\Path $path): string|false
    {
        return false;
    }

    public static function webHook(Representation\WebHook ...$webHooks): string|false
    {
        return 'webhook';
    }
}
