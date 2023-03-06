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

final class WebHookHydrator
{
    public static function gather(
        string $event,
        \ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook ...$webHooks,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator {

        $schemaClasses = [];
        foreach ($webHooks as $webHook) {
            foreach ($webHook->schema as $webHookSchema) {
                foreach (self::listSchemas($webHookSchema) as $schema) {
                    $schemaClasses[] = $schema;
                }
            }
        }

        return Hydrator::gather(
            'WebHook\\' . Utils::className($event),
            ...$schemaClasses,
        );
    }

    /**
     * @return iterable<\ApiClients\Tools\OpenApiClientGenerator\Representation\Schema>
     */
    private static function listSchemas(\ApiClients\Tools\OpenApiClientGenerator\Representation\Schema $schema): iterable
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
            yield $propertyType->payload;
            yield from self::listSchemas($propertyType->payload);
        } else if ($propertyType->payload instanceof PropertyType) {
            yield from self::listSchemasFromPropertyType($propertyType->payload);
        }
    }
}
