<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use cebe\openapi\spec\Schema as baseSchema;
use Jawira\CaseConverter\Convert;

use function count;
use function str_replace;
use function strlen;

final class Property
{
    public static function gather(
        Namespace_ $baseNamespace,
        string $className,
        string $propertyName,
        bool $required,
        baseSchema $property,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Property {
        $exampleData = null;

        /** @phpstan-ignore-next-line */
        if (count($property->examples ?? []) > 0) {
            // Main reason we're doing this is so we cause more variety in the example data when a list of examples is provided, but also consistently pick the same item so we do don't cause code churn
            /** @phpstan-ignore-next-line */
            $exampleData = $property->examples[strlen($propertyName) % 2 ? 0 : count($property->examples) - 1];
        }

        if ($exampleData === null && $property->example !== null) {
            $exampleData = $property->example;
        }

        if ($exampleData === null && count($property->enum ?? []) > 0) {
            // Main reason we're doing this is so we cause more variety in the enum based example data, but also consistently pick the same item so we do don't cause code churn
            /** @phpstan-ignore-next-line */
            $exampleData = $property->enum[strlen($propertyName) % 2 ? 0 : count($property->enum) - 1];
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
            $baseNamespace,
            $className,
            $propertyName,
            $property,
            $required,
            $schemaRegistry,
        );
        if ($type->payload instanceof Schema) {
            if (count($type->payload->properties) === 0) {
                $type = new PropertyType('scalar', null, null, 'mixed', false);
            }
        } elseif ($type->payload instanceof PropertyType && $type->payload->payload instanceof Schema) {
            if (count($type->payload->payload->properties) === 0) {
                $type = new PropertyType('scalar', null, null, 'mixed', false);
            }
        }

        $exampleData = ExampleData::gather($exampleData, $type, $propertyName);

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Property(
            (new Convert($propertyName))->toCamel(),
            $propertyName,
            $property->description ?? '',
            $exampleData,
            [$type],
            $type->nullable
        );
    }
}
