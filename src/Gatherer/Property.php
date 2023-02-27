<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Schema as baseSchema;
use Jawira\CaseConverter\Convert;

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
            $type = new PropertyType(
                'scalar',
                'mixed'
            );
            $nullable = false;
        } else if ($type === 'object') {
            $type = new PropertyType(
                'object',
                Schema::gather(
                    $schemaRegistry->get($property, $className . '\\' . Utils::className($propertyName)),
                    $property,
                    $schemaRegistry,
                )
            );
            $exampleData = $type->payload->example;
        } else {
            if ($type === 'int') {
                $exampleData = 13;
            } elseif ($type === 'bool') {
                $exampleData = false;
            } else {
                $exampleData = 'generated_' . $propertyName;
            }
            $type = new PropertyType(
                'scalar',
                $type
            );
        }

        if (!is_array($type)) {
            $type = [$type];
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Property($propertyName, $property->description ?? '', $exampleData, $type, $nullable);
    }
}
