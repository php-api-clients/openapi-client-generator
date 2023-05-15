<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
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
        Namespace_ $baseNamespace,
        string $className,
        baseSchema $schema,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema {
        $className  = Utils::className($className);
        $isArray    = $schema->type === 'array';
        $properties = [];
        $example    = [];

        if ($isArray) {
            $schema = $schema->items;
        }

        foreach ($schema->properties as $propertyName => $property) {
            $gatheredProperty = Property::gather(
                $baseNamespace,
                $className,
                (string) $propertyName,
                in_array(
                    $propertyName,
                    $schema->required ?? [],
                    false,
                ),
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

            $example[$gatheredProperty->sourceName] = $gatheredProperty->example->raw;
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema(
            ClassString::factory($baseNamespace, 'Schema\\' . $className),
            ClassString::factory($baseNamespace, 'Error\\' . $className),
            ClassString::factory($baseNamespace, 'ErrorSchemas\\' . $className),
            $schema->title ?? '',
            $schema->description ?? '',
            $example,
            $properties,
            $schema,
            $isArray,
        );
    }
}
