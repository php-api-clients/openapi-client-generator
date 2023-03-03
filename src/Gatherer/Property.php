<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Schema as baseSchema;
use Jawira\CaseConverter\Convert;

final class Property
{
    public static function gather(
        string $className,
        string $propertyName,
        bool $required,
        baseSchema $property,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Property {
        $exampleData = null;

        if (count($property->examples ?? []) > 0) {
            $exampleData = $property->examples[count($property->examples) === 1 ? 0 : mt_rand(0, count($property->examples) - 1)];
        } else if ($property->example !== null) {
            $exampleData = $property->example;
        }

        $propertyName = str_replace([
            '@',
            '+',
            '-',
            '$',
        ], [
            '_AT_',
            '_PLUSES_',
            '_MINUS_',
            '',
        ], $propertyName);

        $type = Type::gather(
            $className,
            $propertyName,
            $property,
            $required,
            $schemaRegistry,
        );
        if ($type->type=== 'object') {
            $exampleData = ($exampleData ?? []) + $type->payload->example;
        } else if ($exampleData === null && $type->type=== 'scalar') {
            if ($type->payload === 'int') {
                $exampleData = 13;
            } elseif ($type->payload === 'bool') {
                $exampleData = false;
            } else {
                $exampleData = 'generated_' . $propertyName;
            }
        }

        if ($type->type === 'array') {
            $exampleData = [$exampleData];
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Property($propertyName, $property->description ?? '', $exampleData, [$type], $type->nullable);
    }
}
