<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Parameter;
use cebe\openapi\spec\Operation as openAPIOperation;
use cebe\openapi\spec\PathItem;
use Jawira\CaseConverter\Convert;
use Psr\Http\Message\ResponseInterface;

final class WebHookHydrator
{
    public static function gather(
        string $event,
        \ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook ...$webHooks,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator {

        $schemaClasses = [];
        foreach ($webHooks as $webHook) {
            foreach ($webHook->schema as $schema) {
                $schemaClasses[] = $schema;
            }
        }

        return Hydrator::gather(
            'WebHook\\' . Utils::className($event),
            ...$schemaClasses,
        );
    }
}
