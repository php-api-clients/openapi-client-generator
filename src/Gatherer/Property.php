<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Schema as baseSchema;
use Jawira\CaseConverter\Convert;
use function Rikudou\ArrayMergeRecursive\array_merge_recursive;

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
            $exampleData = $property->examples[0];
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
        if ($type->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
            if (count($type->payload->properties) === 0) {
                $type = new PropertyType('scalar', 'mixed', false);
            }
        }
        $exampleData = self::generateExampleData($exampleData, $type, $propertyName);

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Property($propertyName, $property->description ?? '', $exampleData, [$type], $type->nullable);
    }

    private static function generateExampleData(mixed $exampleData, PropertyType $type, string $propertyName): mixed
    {
        if ($type->type === 'array') {
            if ($type->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
                $exampleData = array_merge_recursive($type->payload->example, $exampleData ?? []);
            } else if ($type->payload instanceof PropertyType) {
                $exampleData = self::generateExampleData($exampleData, $type->payload, $propertyName);
            }
            return [$exampleData];
        }


        if ($type->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
            return array_merge_recursive($type->payload->example, is_array($exampleData) ? $exampleData : []);
        } else if ($exampleData === null && $type->type=== 'scalar') {
            if ($type->payload === 'int') {
                return 13;
            } elseif ($type->payload === 'bool') {
                return false;
            } elseif ($type->payload === 'string') {
                return 'generated_' . $propertyName;
            }
        }

        return $exampleData;
    }
}
