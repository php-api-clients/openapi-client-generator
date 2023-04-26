<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\Schema as baseSchema;

use function array_key_exists;
use function in_array;
use function is_array;
use function property_exists;

final class Schema
{
    public static function gather(
        string $className,
        baseSchema $schema,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema {
        $className  = Utils::fixKeyword($className);
        $isArray    = $schema->type === 'array';
        $properties = [];
        $example    = [];

        if ($isArray) {
            $schema = $schema->items;
        }

        foreach ($schema->properties as $propertyName => $property) {
            $gatheredProperty = Property::gather(
                $className,
                (string) $propertyName,
                is_array($schema->required) && in_array($propertyName, $schema->required, false),
                $property,
                $schemaRegistry
            );
            $properties[]     = $gatheredProperty;

            foreach (['examples', 'example'] as $examplePropertyName) {
                if (array_key_exists($gatheredProperty->sourceName, $example)) {
                    break;
                }

                if (! property_exists($schema, $examplePropertyName) || ! is_array($schema->$examplePropertyName) || ! array_key_exists($gatheredProperty->sourceName, $schema->$examplePropertyName)) {
                    continue;
                }

                $example[$gatheredProperty->sourceName] = $schema->$examplePropertyName[$gatheredProperty->sourceName];
            }

            $example[$gatheredProperty->sourceName] = $gatheredProperty->exampleData;
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema(
            $className,
            $schema->title ?? '',
            $schema->description ?? '',
            $example,
            $properties,
            $schema,
            $isArray,
        );
    }
}
