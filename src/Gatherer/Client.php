<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Parameter;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation as openAPIOperation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Server;
use Jawira\CaseConverter\Convert;
use Psr\Http\Message\ResponseInterface;

final class Client
{
    public static function gather(
        OpenApi                                                      $spec,
        \ApiClients\Tools\OpenApiClientGenerator\Representation\Path ...$paths,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Client {
        $baseUrl = null;
        foreach ($spec->servers ?? [] as $server) {
            if (!($server instanceof Server)) {
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
