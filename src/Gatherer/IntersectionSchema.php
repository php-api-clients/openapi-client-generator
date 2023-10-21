<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Registry\CompositSchema as CompositSchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Contract as ContractRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Contract;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\Schema as baseSchema;

use function array_key_exists;
use function in_array;
use function is_array;
use function property_exists;

final class IntersectionSchema
{
    public static function gather(
        Namespace_ $baseNamespace,
        string $className,
        baseSchema $baseProperty,
        SchemaRegistry $schemaRegistry,
        ContractRegistry $contractRegistry,
        CompositSchemaRegistry $compositSchemaRegistry,
    ): Schema {
        $className  = Utils::className($className);
        $contracts  = [];
        $properties = [];
        $example    = [];

        foreach ($baseProperty->allOf as $schema) {
            $gatheredProperties = [];
            foreach ($schema->properties as $propertyName => $property) {
                $gatheredProperty = $gatheredProperties[(string) $propertyName]                            = Property::gather(
                    $baseNamespace,
                    $className,
                    (string) $propertyName,
                    in_array(
                        (string) $propertyName,
                        $schema->required ?? [],
                        false,
                    ),
                    $property,
                    $schemaRegistry,
                    $contractRegistry,
                    $compositSchemaRegistry,
                );

                $example[$gatheredProperty->sourceName] = $gatheredProperty->example->raw;

                foreach (['examples', 'example'] as $examplePropertyName) {
                    if (array_key_exists($gatheredProperty->sourceName, $example)) {
                        break;
                    }

                    if (! property_exists($schema, $examplePropertyName) || ! is_array($schema->$examplePropertyName) || ! array_key_exists($gatheredProperty->sourceName, $schema->$examplePropertyName)) {
                        continue;
                    }

                    $example[$gatheredProperty->sourceName] = $schema->$examplePropertyName[$gatheredProperty->sourceName];
                }

                foreach ($property->enum ?? [] as $value) {
                    $example[$gatheredProperty->sourceName] = $value;
                    break;
                }

                if ($example[$gatheredProperty->sourceName] !== null || $property->required || $baseProperty->required) {
                    continue;
                }

                unset($example[$gatheredProperty->sourceName]);
            }

            $contracts[] = new Contract(
                ClassString::factory(
                    $baseNamespace,
                    $contractRegistry->get($schema, 'Contract\\' . $className . '\\' . $schema->title),
                ),
                $gatheredProperties,
            );

            $properties = [...$properties, ...$gatheredProperties];
        }

        return new Schema(
            ClassString::factory($baseNamespace, 'Schema\\' . $className),
            $contracts,
            ClassString::factory($baseNamespace, 'Error\\' . $className),
            ClassString::factory($baseNamespace, 'ErrorSchemas\\' . $className),
            $baseProperty->title ?? '',
            $baseProperty->description ?? '',
            $example,
            $properties,
            $baseProperty,
            false,
            ($baseProperty->type === null ? ['object'] : (is_array($baseProperty->type) ? $baseProperty->type : [$baseProperty->type])),
        );
    }
}
