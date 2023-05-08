<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\Schema as baseSchema;

use function array_filter;
use function count;
use function current;
use function in_array;
use function is_array;
use function is_string;
use function str_replace;

final class Type
{
    public static function gather(
        Namespace_ $baseNamespace,
        string $className,
        string $propertyName,
        baseSchema $property,
        bool $required,
        SchemaRegistry $schemaRegistry,
    ): PropertyType {
        if (is_array($property->allOf) && count($property->allOf) > 0) {
            return self::gather(
                $baseNamespace,
                $className,
                $propertyName,
                $property->allOf[0],
                $required,
                $schemaRegistry,
            );
        }

        if (is_array($property->oneOf) && count($property->oneOf) > 0) {
            // Check if nullable
            if (
                count($property->oneOf) === 2 &&
                count(array_filter($property->oneOf, static fn (\cebe\openapi\spec\Schema $schema): bool => $schema->type === 'null')) === 1
            ) {
                return self::gather(
                    $baseNamespace,
                    $className,
                    $propertyName,
                    current(array_filter($property->oneOf, static fn (\cebe\openapi\spec\Schema $schema): bool => $schema->type !== 'null')),
                    false,
                    $schemaRegistry,
                );
            }

            return self::gather(
                $baseNamespace,
                $className,
                $propertyName,
                $property->oneOf[0],
                $required,
                $schemaRegistry,
            );
        }

        if (is_array($property->anyOf) && count($property->anyOf) > 0) {
            // Check if nullable
            if (
                count($property->anyOf) === 2 &&
                count(array_filter($property->anyOf, static fn (\cebe\openapi\spec\Schema $schema): bool => $schema->type === 'null')) === 1
            ) {
                return self::gather(
                    $baseNamespace,
                    $className,
                    $propertyName,
                    current(array_filter($property->anyOf, static fn (\cebe\openapi\spec\Schema $schema): bool => $schema->type !== 'null')),
                    false,
                    $schemaRegistry,
                );
            }

            return self::gather(
                $baseNamespace,
                $className,
                $propertyName,
                $property->anyOf[0],
                $required,
                $schemaRegistry,
            );
        }

        $type     = $property->type;
        $nullable = ! $required;

        if (
            is_array($type) &&
            count($type) === 2 &&
            (
                in_array(null, $type, false) ||
                in_array('null', $type, false)
            )
        ) {
            foreach ($type as $pt) {
                /** @phpstan-ignore-next-line */
                if ($pt !== null && $pt !== 'null') {
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
                null,
                self::gather(
                    $baseNamespace,
                    $className,
                    $propertyName,
                    $property->items,
                    $required,
                    $schemaRegistry,
                ),
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
                null,
                'mixed',
                false,
            );
        }

        if ($type === 'object') {
//            echo Utils::className($className . '\\' . $propertyName), PHP_EOL;
            return new PropertyType(
                'object',
                null,
                null,
                Schema::gather(
                    $baseNamespace,
                    $schemaRegistry->get(
                        $property,
                        Utils::className($className . '\\' . $propertyName),
                    ),
                    $property,
                    $schemaRegistry,
                ),
                $nullable,
            );
        }

        return new PropertyType(
            'scalar',
            $property->format ?? null,
            $property->pattern ?? null,
            $type,
            $nullable,
        );
    }
}
