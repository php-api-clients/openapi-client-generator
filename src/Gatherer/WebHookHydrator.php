<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook;
use ApiClients\Tools\OpenApiClientGenerator\Utils;

final class WebHookHydrator
{
    public static function gather(
        Namespace_ $baseNamespace,
        string $event,
        WebHook ...$webHooks,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator {
        $schemaClasses = [];
        foreach ($webHooks as $webHook) {
            foreach ($webHook->schema as $webHookSchema) {
                foreach (HydratorUtils::listSchemas($webHookSchema) as $schema) {
                    $schemaClasses[] = $schema;
                }
            }
        }

        return Hydrator::gather(
            $baseNamespace,
            'WebHook\\' . Utils::className($event),
            '🪝',
            ...$schemaClasses,
        );
    }
}
