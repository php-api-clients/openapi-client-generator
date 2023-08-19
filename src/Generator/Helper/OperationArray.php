<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;

use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use cebe\openapi\spec\Schema as cebeSchema;
use PhpParser\Node;
use PhpParser\Node\Arg;

use function is_array;
use function is_string;

final class OperationArray
{
    public static function hydrate(string $className): Node\Expr\MethodCall
    {
        return new Node\Expr\MethodCall(
            new Node\Expr\PropertyFetch(
                new Node\Expr\Variable('this'),
                'hydrator',
            ),
            'hydrateObject',
            [
                new Node\Arg(new Node\Expr\ClassConstFetch(
                    new Node\Name($className),
                    'class',
                )),
                new Node\Arg(new Node\Expr\Variable('body')),
            ],
        );
    }

    public static function validate(string $className, bool $isArray): Node\Stmt\Expression
    {
        return new Node\Stmt\Expression(new Node\Expr\MethodCall(
            new Node\Expr\PropertyFetch(
                new Node\Expr\Variable('this'),
                'responseSchemaValidator',
            ),
            'validate',
            [
                new Node\Arg(new Node\Expr\Variable($isArray ? 'bodyItem' : 'body')),
                new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), 'readFromJson', [
                    new Arg(new Node\Expr\ClassConstFetch(
                        new Node\Name($className),
                        'SCHEMA_JSON',
                    )),
                    new Arg(new Node\Expr\ClassConstFetch(
                        new Node\Name('\\' . cebeSchema::class),
                        'class',
                    )),
                ])),
            ],
        ));
    }

    /** @return iterable<Schema> */
    public static function uniqueSchemas(string|Schema|PropertyType ...$propertyTypes): iterable
    {
        $schemas = [];

        foreach ($propertyTypes as $propertyType) {
            if (is_string($propertyType)) {
                $schemas[$propertyType] = $propertyType;
                continue;
            }

            if ($propertyType instanceof Schema) {
                $schemas[$propertyType->className->fullyQualified->source] = $propertyType;
                continue;
            }

            foreach (
                static::uniqueSchemas(...is_array($propertyType->payload) ? $propertyType->payload : [$propertyType->payload]) as $nestedPropertyType
            ) {
                $schemas[$nestedPropertyType instanceof Schema ? $nestedPropertyType->className->fullyQualified->source : $nestedPropertyType] = $nestedPropertyType;
            }
        }

        yield from $schemas;
    }
}
