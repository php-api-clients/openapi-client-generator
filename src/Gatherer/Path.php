<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Voter;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\PathItem;

use function is_array;
use function strlen;

final class Path
{
    public static function gather(
        Namespace_ $baseNamespace,
        string $className,
        string $path,
        PathItem $pathItem,
        SchemaRegistry $schemaRegistry,
        ThrowableSchema $throwableSchemaRegistry,
        ?Voter $voters,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Path {
        $className  = Utils::fixKeyword($className);
        $operations = [];

        foreach ($pathItem->getOperations() as $method => $operation) {
            $operationClassName = Utils::className($operation->operationId);
            if (strlen($operationClassName) === 0) {
                continue;
            }

            $operations[] = $opp = Operation::gather(
                $baseNamespace,
                $operationClassName,
                $method,
                $method,
                $path,
                [],
                $operation,
                $throwableSchemaRegistry,
                $schemaRegistry,
            );

            if ($voters !== null && is_array($voters->listOperation)) {
                $shouldStream = false;
                $voter        = null;
                /** @phpstan-ignore-next-line */
                foreach ($voters->listOperation as $voter) {
                    if ($voter::list($opp)) {
                        $shouldStream = true;
                        break;
                    }
                }

                if ($voter !== null && $shouldStream) {
                    $operations[] = Operation::gather(
                        $baseNamespace,
                        $operationClassName . 'Listing',
                        'LIST',
                        $method,
                        $path,
                        [
                            'listOperation' => [
                                'key' => $voter::incrementorKey(),
                                'initialValue' => $voter::incrementorInitialValue(),
                                'keys' => $voter::keys(),
                            ],
                        ],
                        $operation,
                        $throwableSchemaRegistry,
                        $schemaRegistry,
                    );
                }
            }

            if ($voters === null || ! is_array($voters->streamOperation)) {
                continue;
            }

            $shouldStream = false;
            foreach ($voters->streamOperation as $voter) {
                if ($voter::stream($opp)) {
                    $shouldStream = true;
                    break;
                }
            }

            if (! $shouldStream) {
                continue;
            }

            $operations[] = Operation::gather(
                $baseNamespace,
                $operationClassName . 'Streaming',
                'STREAM',
                $method,
                $path,
                [],
                $operation,
                $throwableSchemaRegistry,
                $schemaRegistry,
            );
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Path(
            ClassString::factory($baseNamespace, $className),
            OperationHydrator::gather(
                $baseNamespace,
                $className,
                ...$operations,
            ),
            $operations,
        );
    }
}
