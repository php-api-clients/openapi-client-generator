<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\Schema as baseSchema;

use function array_key_exists;
use function in_array;
use function is_array;

final class Schema
{
    public static function gather(
        string $className,
        baseSchema $schema,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema {
        $className = Utils::fixKeyword($className);
        $isArray   = $schema->type === 'array';
        if ($isArray) {
            $schema = $schema->items;
        }

        $properties = [];
        $example    = $schema->example ?? [];
        foreach ($schema->properties as $propertyName => $property) {
            $gatheredProperty = Property::gather(
                $className,
                (string) $propertyName,
                is_array($schema->required) && in_array($propertyName, $schema->required, false),
                $property,
                $schemaRegistry
            );
            $properties[]     = $gatheredProperty;

            if (array_key_exists($gatheredProperty->sourceName, $example)) {
                continue;
            }

            $example[$gatheredProperty->sourceName] = $gatheredProperty->exampleData;
        }

//        if ($className === 'WebhookWorkflowRunCompleted\WorkflowRun') {
//            var_export([
//                $className,
//                $schema->title ?? '',
//                $schema->description ?? '',
//                $example,
////                $properties,
//                $isArray,
//            ]);
//        }

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
