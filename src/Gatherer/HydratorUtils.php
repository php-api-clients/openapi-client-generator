<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Parameter;
use cebe\openapi\spec\Operation as openAPIOperation;
use cebe\openapi\spec\PathItem;
use Jawira\CaseConverter\Convert;
use Psr\Http\Message\ResponseInterface;

final class HydratorUtils
{
    /**
     * @return iterable<\ApiClients\Tools\OpenApiClientGenerator\Representation\Schema>
     */
    public static function listSchemas(\ApiClients\Tools\OpenApiClientGenerator\Representation\Schema $schema): iterable
    {
        yield $schema;
        foreach ($schema->properties as $property) {
            foreach ($property->type as $propertyType) {
                yield from self::listSchemasFromPropertyType($propertyType);
            }
        }
    }

    private static function listSchemasFromPropertyType(PropertyType $propertyType)
    {
        if ($propertyType->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
            yield from self::listSchemas($propertyType->payload);
        } else if ($propertyType->payload instanceof PropertyType) {
            yield from self::listSchemasFromPropertyType($propertyType->payload);
        }
    }
}
