<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Server;

use function strlen;

final class Client
{
    public static function gather(
        OpenApi $spec,
        Path ...$paths,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Client {
        $baseUrl = null;
        foreach ($spec->servers ?? [] as $server) {
            if (! ($server instanceof Server)) {
                continue;
            }

            if (strlen($server->url) === 0) {
                continue;
            }

            $baseUrl = $server->url;
            break;
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Client(
            $baseUrl,
            $paths,
        );
    }
}
