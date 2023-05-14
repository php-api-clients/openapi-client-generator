<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;

final class HydratorUtils
{
    /**
     * @return iterable<Schema>
     */
    public static function listSchemas(Schema $schema): iterable
    {
        yield $schema;

        foreach ($schema->properties as $property) {
            foreach ($property->type as $propertyType) {
                yield from self::listSchemasFromPropertyType($propertyType);
            }
        }
    }

    /**
     * @return iterable<Schema>
     */
    private static function listSchemasFromPropertyType(PropertyType $propertyType): iterable
    {
        if ($propertyType->payload instanceof Schema) {
            yield from self::listSchemas($propertyType->payload);
        } elseif ($propertyType->payload instanceof PropertyType) {
            yield from self::listSchemasFromPropertyType($propertyType->payload);
        }
    }
}
