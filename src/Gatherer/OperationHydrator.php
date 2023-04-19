<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;

final class OperationHydrator
{
    public static function gather(
        string $className,
        Operation ...$operations,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator {
        $schemaClasses = [];

        foreach ($operations as $operation) {
            foreach ($operation->response as $response) {
                foreach (HydratorUtils::listSchemas($response->schema) as $schema) {
                    $schemaClasses[] = $schema;
                }
            }
        }

        return Hydrator::gather(
            'Operation\\' . $className,
            '🌀',
            ...$schemaClasses,
        );
    }
}
