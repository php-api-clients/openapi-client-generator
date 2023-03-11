<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Contract\Voter\StreamOperation;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\PathItem;

final class Path
{
    /**
     * @param array{streamOperation: array<StreamOperation>} $voters
     */
    public static function gather(
        string $className,
        string $path,
        PathItem $pathItem,
        SchemaRegistry $schemaRegistry,
        array $voters,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Path {
        $className = Utils::fixKeyword($className);
        $operations = [];

        foreach ($pathItem->getOperations() as $method => $operation) {
            $operationClassName = Utils::className($operation->operationId);
            if (strlen($operationClassName) === 0) {
                continue;
            }

            $operations[] = $opp = Operation::gather(
                $operationClassName,
                $method,
                $method,
                $path,
                $operation,
                $schemaRegistry,
            );

            if (array_key_exists('streamOperation', $voters)) {
                $shouldStream = false;
                foreach ($voters['streamOperation'] as $voter) {
                    if ($voter::stream($opp)) {
                        $shouldStream = true;
                        break;
                    }
                }
                if ($shouldStream) {
                    $operations[] = Operation::gather(
                        $operationClassName . 'Streaming',
                        'STREAM',
                        $method,
                        $path,
                        $operation,
                        $schemaRegistry,
                    );
                }
            }
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Path(
            $className,
            OperationHydrator::gather(
                $className,
                ...$operations,
            ),
            $operations,
        );
    }
}
