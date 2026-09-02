<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;

use OpenAPITools\Representation\Contract;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Representation\Property;
use OpenAPITools\Representation\Schema;

use function array_map;
use function is_string;

final class Representation
{
    public static function propertyType(Namespaced\Property\Type $type): Property\Type
    {
        return new Property\Type(
            $type->type,
            $type->format,
            $type->pattern,
            self::typePayload($type->payload),
            $type->nullable,
        );
    }

    /**
     * @param string|Namespaced\Schema|Namespaced\Property\Type|array<Namespaced\Property\Type> $payload
     *
     * @return string|Schema|Property\Type|array<Property\Type>
     */
    private static function typePayload(string|Namespaced\Schema|Namespaced\Property\Type|array $payload): string|Schema|Property\Type|array
    {
        if (is_string($payload)) {
            return $payload;
        }

        if ($payload instanceof Namespaced\Schema) {
            return self::schema($payload);
        }

        if ($payload instanceof Namespaced\Property\Type) {
            return self::propertyType($payload);
        }

        return array_map(self::propertyType(...), $payload);
    }

    public static function schema(Namespaced\Schema $schema): Schema
    {
        return new Schema(
            $schema->className->relative,
            array_map(self::contract(...), $schema->contracts),
            $schema->errorClassName->relative,
            $schema->errorClassNameAliased->relative,
            $schema->title,
            $schema->description,
            $schema->example,
            array_map(self::property(...), $schema->properties),
            $schema->schema,
            $schema->isArray,
            $schema->type,
            $schema->alias,
        );
    }

    private static function contract(Namespaced\Contract $contract): Contract
    {
        return new Contract(
            $contract->className->relative,
            array_map(self::property(...), $contract->properties),
        );
    }

    private static function property(Namespaced\Property $property): Property
    {
        return new Property(
            $property->name,
            $property->sourceName,
            $property->description,
            $property->example,
            self::propertyType($property->type),
            $property->nullable,
            $property->enum,
        );
    }
}
