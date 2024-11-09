<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Contract;

use OpenAPITools\Representation;

interface SectionGenerator
{
    public static function path(Representation\Namespaced\Path $path): string|false;

    public static function webHook(Representation\WebHook ...$webHooks): string|false;
}
