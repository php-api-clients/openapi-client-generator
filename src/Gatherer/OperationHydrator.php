<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;

final class OperationHydrator
{
    public static function gather(
        Namespace_ $baseNamespace,
        string $className,
        Operation ...$operations,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator {
        $schemaClasses = [];

        foreach ($operations as $operation) {
            foreach ($operation->response as $response) {
                if (! ($response->content->payload instanceof Schema)) {
                    continue;
                }

                foreach (HydratorUtils::listSchemas($response->content->payload) as $schema) {
                    $schemaClasses[] = $schema;
                }
            }
        }

        return Hydrator::gather(
            $baseNamespace,
            'Operation\\' . $className,
            '🌀',
            ...$schemaClasses,
        );
    }
}
