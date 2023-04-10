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
        if (is_array($property->allOf) && count($property->allOf) > 0) {
            return self::gather(
                $className,
                $propertyName,
                $property->allOf[0],
                $required,
                $schemaRegistry,
            );
        } else if (is_array($property->oneOf) && count($property->oneOf) > 0) {
            // Check if nullable
            if (
                count($property->oneOf) === 2 &&
                count(array_filter($property->oneOf, static fn (\cebe\openapi\spec\Schema $schema): bool => $schema->type === 'null')) === 1
            ) {
                return self::gather(
                    $className,
                    $propertyName,
                    current(array_filter($property->oneOf, static fn (\cebe\openapi\spec\Schema $schema): bool => $schema->type !== 'null')),
                    false,
                    $schemaRegistry,
                );
            }

            return self::gather(
                $className,
                $propertyName,
                $property->oneOf[0],
                $required,
                $schemaRegistry,
            );
        } else if (is_array($property->anyOf) && count($property->anyOf) > 0) {
            // Check if nullable
            if (
                count($property->anyOf) === 2 &&
                count(array_filter($property->anyOf, static fn (\cebe\openapi\spec\Schema $schema): bool => $schema->type === 'null')) === 1
            ) {
                return self::gather(
                    $className,
                    $propertyName,
                    current(array_filter($property->anyOf, static fn (\cebe\openapi\spec\Schema $schema): bool => $schema->type !== 'null')),
                    false,
                    $schemaRegistry,
                );
            }

            return self::gather(
                $className,
                $propertyName,
                $property->anyOf[0],
                $required,
                $schemaRegistry,
            );
        }

        $type = $property->type;
        $nullable = !$required;

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

        if ($type === 'array') {
            return new PropertyType(
                'array',
                null,
                Type::gather($className, $propertyName, $property->items, $required, $schemaRegistry),
                $nullable
            );
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
                'int|float',
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
                null,
                'mixed',
                false,
            );
        }

        if ($type === 'object') {
            return new PropertyType(
                'object',
                null,
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
            $property->format ?? null,
            $type,
            $nullable,
        );
    }
}
