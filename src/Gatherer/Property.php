<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use cebe\openapi\spec\Schema as baseSchema;
use Ckr\Util\ArrayMerger;

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
        } else if ($type->payload instanceof PropertyType && $type->payload->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
            if (count($type->payload->payload->properties) === 0) {
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
                $exampleData = ArrayMerger::doMerge($type->payload->example, $exampleData ?? [], ArrayMerger::FLAG_OVERWRITE_NUMERIC_KEY);
            } else if ($type->payload instanceof PropertyType) {
                $exampleData = self::generateExampleData($exampleData, $type->payload, $propertyName);
            }
            return [$exampleData];
        }


        if ($type->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
            return ArrayMerger::doMerge($type->payload->example, is_array($exampleData) ? $exampleData : [], ArrayMerger::FLAG_OVERWRITE_NUMERIC_KEY);
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
