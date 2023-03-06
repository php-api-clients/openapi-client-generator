<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

final class OperationHydrator
{
    public static function gather(
        string $className,
        \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation ...$operations,
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
