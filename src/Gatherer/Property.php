<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation;
use cebe\openapi\spec\Schema as baseSchema;
use Jawira\CaseConverter\Convert;
use NumberToWords\NumberToWords;
use PhpParser\Node;

use function array_filter;
use function array_values;
use function count;
use function is_array;
use function preg_replace_callback;
use function str_pad;
use function str_replace;
use function strlen;

final class Property
{
    public static function gather(
        Namespace_ $baseNamespace,
        string $className,
        string $sourcePropertyName,
        bool $required,
        baseSchema $property,
        SchemaRegistry $schemaRegistry,
    ): Representation\Property {
        $enum        = [];
        $exampleData = null;

        /** @phpstan-ignore-next-line */
        if (count($property->examples ?? []) > 0) {
            $examples = array_values(array_filter($property->examples, static fn (mixed $value): bool => $value !== null));
            // Main reason we're doing this is so we cause more variety in the example data when a list of examples is provided, but also consistently pick the same item so we do don't cause code churn
            /** @phpstan-ignore-next-line */
            $exampleData = $examples[strlen($sourcePropertyName) % 2 ? 0 : count($examples) - 1];
        }

        if ($exampleData === null && $property->example !== null) {
            $exampleData = $property->example;
        }

        if ($exampleData === null && count($property->enum ?? []) > 0) {
            $enum  = $property->enum;
            $enums = array_values(array_filter($property->enum, static fn (mixed $value): bool => $value !== null));
            // Main reason we're doing this is so we cause more variety in the enum based example data, but also consistently pick the same item so we do don't cause code churn
            /** @phpstan-ignore-next-line */
            $exampleData = $enums[strlen($sourcePropertyName) % 2 ? 0 : count($enums) - 1];
        }

        $propertyName = str_replace([
            '@',
            '+',
            '-',
            '$',
        ], [
            '_AT_',
            '_PLUS_',
            '_MIN_',
            '_DOLLAR_',
        ], $sourcePropertyName);
        $propertyName = preg_replace_callback(
            '/[0-9]+/',
            static function ($matches) {
                return '_' . str_replace(['-', ' '], '_', NumberToWords::transformNumber('en', (int) $matches[0])) . '_';
            },
            $propertyName,
        );

        $type = Type::gather(
            $baseNamespace,
            $className,
            $propertyName,
            $property,
            $required,
            $schemaRegistry,
        );
        if ($type->payload instanceof Representation\Schema) {
            if (count($type->payload->properties) === 0) {
                $type = new Representation\PropertyType('scalar', null, null, 'string', false);
            }
        } elseif ($type->payload instanceof Representation\PropertyType && $type->payload->payload instanceof Representation\Schema) {
            if (count($type->payload->payload->properties) === 0) {
                $type = new Representation\PropertyType('scalar', null, null, 'string', false);
            }
        }

        if ($property->type === 'array' && is_array($type->payload)) {
            $arrayItemsRaw  = [];
            $arrayItemsNode = [];

            foreach ($type->payload as $index => $arrayItem) {
                $arrayItemExampleData = ExampleData::gather($exampleData, $arrayItem, $propertyName . str_pad('', $index + 1, '_'));
                $arrayItemsRaw[]      = $arrayItemExampleData->raw;
                $arrayItemsNode[]     = new Node\Expr\ArrayItem($arrayItemExampleData->node);
            }

            $exampleData = new Representation\ExampleData($arrayItemsRaw, new Node\Expr\Array_($arrayItemsNode));
        } else {
            $exampleData = ExampleData::gather($exampleData, $type, $propertyName);
        }

        return new Representation\Property(
            (new Convert($propertyName))->toCamel(),
            $sourcePropertyName,
            $property->description ?? '',
            $exampleData,
            $type,
            $type->nullable,
            $enum,
        );
    }
}
