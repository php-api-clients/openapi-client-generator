<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Schema as baseSchema;
use Jawira\CaseConverter\Convert;

final class Type
{
    public static function gather(
        string $className,
        string $propertyName,
        baseSchema $property,
        bool $required,
        SchemaRegistry $schemaRegistry,
    ): PropertyType {
        if (is_array($property->allOf)) {
            return self::gather(
                $className,
                $propertyName,
                $property->allOf[array_keys($property->allOf)[count(array_keys($property->allOf)) - 1]],
                $required,
                $schemaRegistry,
            );
        } else if (is_array($property->oneOf)) {
            return self::gather(
                $className,
                $propertyName,
                $property->oneOf[array_keys($property->oneOf)[count(array_keys($property->oneOf)) - 1]],
                $required,
                $schemaRegistry,
            );
        } else if (is_array($property->anyOf)) {
            return self::gather(
                $className,
                $propertyName,
                $property->anyOf[array_keys($property->anyOf)[count(array_keys($property->anyOf)) - 1]],
                $required,
                $schemaRegistry,
            );
        }

        $type = $property->type;
        $nullable = !$required;

        if ($type === 'array') {
            return new PropertyType(
                'array',
                Type::gather($className, $propertyName, $property->items, $required, $schemaRegistry),
                $nullable
            );
        }

        if (
            is_array($type) &&
            count($type) === 2 &&
            (
                in_array(null, $type) ||
                in_array("null", $type)
            )
        ) {
            foreach ($type as $pt) {
                if ($pt !== null && $pt !== "null") {
                    $type = $pt;
                    break;
                }
            }

            $nullable = true;
        }

        if (is_string($type)) {
            $type = str_replace([
                'integer',
                'number',
                'any',
                'null',
                'boolean',
            ], [
                'int',
                'int',
                '',
                '',
                'bool',
            ], $type);
        } else {
            $type = '';
        }

        if ($type === '') {
            return new PropertyType(
                'scalar',
                'mixed',
                false,
            );
        }

        if ($type === 'object') {
            return new PropertyType(
                'object',
                Schema::gather(
                    $schemaRegistry->get($property, $className . '\\' . Utils::className($propertyName)),
                    $property,
                    $schemaRegistry,
                ),
                $nullable,
            );
        }

        return new PropertyType(
            'scalar',
            $type,
            $nullable,
        );
    }
}
