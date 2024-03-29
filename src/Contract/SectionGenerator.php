<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Contract;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook;

interface SectionGenerator
{
    public static function path(Path $path): string|false;

    public static function webHook(WebHook ...$webHooks): string|false;
}
