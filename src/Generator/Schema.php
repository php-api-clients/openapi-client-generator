<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\SchemaRegistry;
use cebe\openapi\spec\Schema as OpenAPiSchema;
use Jawira\CaseConverter\Convert;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use RingCentral\Psr7\Request;

final class Schema
{
    /**
     * @param string $name
     * @param string $namespace
     * @param string $className
     * @param OpenAPiSchema $schema
     * @return iterable<Node>
     */
    public static function generate(string $name, string $namespace, string $className, OpenAPiSchema $schema, SchemaRegistry $schemaRegistry, string $rootNamespace): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal()->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_JSON',
                        new Node\Scalar\String_(
                            json_encode($schema->getSerializableData())
                        )
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_TITLE',
                        new Node\Scalar\String_(
                            $schema->title ?? $name
                        )
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_DESCRIPTION',
                        new Node\Scalar\String_(
                            $schema->description ?? ''
                        )
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        );

        if ($schema->oneOf !== null && count($schema->oneOf) > 0 && $schema->oneOf[0] instanceof OpenAPiSchema) {
            yield from self::fillUpSchema($name, $namespace, $className, $class, $schema->oneOf[0], $factory, $schemaRegistry, $rootNamespace);
        } else {
            yield from self::fillUpSchema($name, $namespace, $className, $class, $schema, $factory, $schemaRegistry, $rootNamespace);
        }

        yield new File($namespace . '\\' . $className, $stmt->addStmt($class)->getNode());
    }

    private static function fillUpSchema(string $name, string $namespace, string $className, \PhpParser\Builder\Class_ $class, OpenAPiSchema $schema, $factory, SchemaRegistry $schemaRegistry, string $rootNamespace): iterable
    {
        yield from [];
        foreach ($schema->properties as $propertyName => $property) {
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
            $propertyStmt = $factory->property($propertyName)->makePrivate();
            $propertyDocBlock = [];
            $methodDocBlock = [];
            if (strlen($property->description) > 0) {
                $propertyDocBlock[] = $property->description;
                $methodDocBlock[] = $property->description;
            }
            $method = $factory->method($propertyName)->makePublic()/*->setReturnType('string')*/->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $propertyName
                    )
                )
            );
            $propertyType = $property->type;
            $setDefaylt = true;
            $nullable = '';
            if ($property->nullable) {
                $nullable = '?';
                $propertyStmt->setDefault(null);
            }

            if (
                is_array($propertyType) &&
                count($propertyType) === 2 &&
                (
                    in_array(null, $propertyType) ||
                    in_array("null", $propertyType)
                )
            ) {
                foreach ($propertyType as $pt) {
                    if ($pt !== null && $pt !== "null") {
                        $propertyType = $pt;
                        break;
                    }
                }

                $nullable = '?';
            }

            if (is_string($propertyType)) {
                if (is_array($schema->required) && !in_array($propertyName, $schema->required, false)) {
                    $nullable = '?';
                    $propertyStmt->setDefault(null);
                }

                if ($propertyType === 'array' && $property->items instanceof OpenAPiSchema) {
//                    if (array_key_exists(spl_object_hash($property->items), $schemaClassNameMap)) {
                        $methodDocBlock[] = '@return array<\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->items, $className . '\\' . (new Convert($propertyName))->toPascal()) . '>';
                        $propertyDocBlock[] = '@var array<\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->items, $className . '\\' . (new Convert($propertyName))->toPascal()) . '>';
                        $propertyDocBlock[] = '@\WyriHaximus\Hydrator\Attribute\HydrateArray(\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->items, $className . '\\' . (new Convert($propertyName))->toPascal()) . '::class)';
//                    } elseif ($property->items->type === 'object') {
//                        yield from self::generate($name . '::' . $propertyName, $namespace . '\\' . $className, (new Convert($propertyName))->toPascal(), $property->items, $schemaClassNameMap, $rootNamespace);
//                        $methodDocBlock[] = '@return array<\\' . $namespace . '\\' . $className . '\\' . (new Convert($propertyName))->toPascal() . '>';
//                        $propertyDocBlock[] = '@var array<\\' . $namespace . '\\' . $className . '\\' . (new Convert($propertyName))->toPascal() . '>';
//                        $propertyDocBlock[] = '@\WyriHaximus\Hydrator\Attribute\HydrateArray(\\' . $namespace . '\\' . $className . '\\' . (new Convert($propertyName))->toPascal() . '::class)';
//                    }
                }


                if (is_string($propertyType)) {
                    $t = str_replace([
                        'object',
                        'integer',
                        'number',
                        'any',
                        'null',
                        'boolean',
                    ], [
                        'array',
                        'int',
                        'int',
                        '',
                        '',
                        'bool',
                    ], $propertyType);

                    if ($t !== '') {
                        $propertyStmt->setType(($t === 'array' ? '' : $nullable) . $t);
                        $method->setReturnType(($t === 'array' ? '' : $nullable) . $t);
                    }
                }
            }

            // 74908

            if (is_array($property->anyOf) && $property->anyOf[0] instanceof OpenAPiSchema/* && array_key_exists(spl_object_hash($property->anyOf[0]), $schemaClassNameMap)*/) {
                $fqcnn = '\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->anyOf[0], $className . '\\' . (new Convert($propertyName))->toPascal());
                $propertyStmt->setType($nullable . $fqcnn);
                $method->setReturnType($nullable . $fqcnn);
                $propertyDocBlock[] = '@\WyriHaximus\Hydrator\Attribute\Hydrate(' . $fqcnn . '::class)';
                $setDefaylt = false;
            } else if (is_array($property->allOf) && $property->allOf[0] instanceof OpenAPiSchema/* && array_key_exists(spl_object_hash($property->allOf[0]), $schemaClassNameMap)*/) {
                $fqcnn = '\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->allOf[0], $className . '\\' . (new Convert($propertyName))->toPascal());
                $propertyStmt->setType($nullable . $fqcnn);
                $method->setReturnType($nullable . $fqcnn);
                $propertyDocBlock[] = '@\WyriHaximus\Hydrator\Attribute\Hydrate(' . $fqcnn . '::class)';
                $setDefaylt = false;
            }

//            if (($property->type  === 'object' || (is_array($property->type) && count($property->type) === 2)) && $property instanceof OpenAPiSchema/* && array_key_exists(spl_object_hash($property), $schemaClassNameMap)*/) {
            if ($propertyType  === 'object') {
                $fqcnn = '\\' . $rootNamespace . '\\' . $schemaRegistry->get($property, $className . '\\' . (new Convert($propertyName))->toPascal());
                $propertyStmt->setType($nullable . $fqcnn);
                $method->setReturnType($nullable . $fqcnn);
                $propertyDocBlock[] = '@\WyriHaximus\Hydrator\Attribute\Hydrate(' . $fqcnn . '::class)';
                $setDefaylt = false;
            }

            if (is_string($propertyType)) {
                $t = str_replace([
                    'object',
                    'integer',
                    'any',
                    'boolean',
                ], [
                    'array',
                    'int',
                    '',
                    'bool',
                ], $propertyType);
                if ($t !== '') {
                    if ($t === 'array' && $setDefaylt === true) {
                        $propertyStmt->setDefault([]);
                    }
                }
            }

            if (count($propertyDocBlock) > 0) {
                $propertyStmt->setDocComment('/**' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', $propertyDocBlock) . PHP_EOL .' */');
            }

            if (count($methodDocBlock) > 0) {
                $method->setDocComment('/**' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', $methodDocBlock) . PHP_EOL .' */');
            }

            $class->addStmt($propertyStmt)->addStmt($method);
        }
    }
}
