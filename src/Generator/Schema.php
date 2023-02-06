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

        $schemaJson = new Node\Stmt\ClassConst(
            [
                new Node\Const_(
                    'SCHEMA_JSON',
                    new Node\Scalar\String_(
                        json_encode($schema->getSerializableData())
                    )
                ),
            ],
            Class_::MODIFIER_PUBLIC
        );

        if ($schema->type === 'array') {
            $schema = $schema->items;
        }

        $class = $factory->class($className)->makeFinal()->addStmt(
            $schemaJson
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_EXAMPLE',
                        new Node\Scalar\String_(
                            json_encode((function (array $schema): array {
                                $iterate = function (array $schema) use (&$iterate): array {
                                    $examples = [];

                                    if (!array_key_exists('properties', $schema)) {
                                        return $examples;
                                    }
                                    foreach ($schema['properties'] as $propertyName => $property) {
                                        if (
                                            array_key_exists('type', $property) &&
                                            $property['type'] === 'object' &&
                                            array_key_exists('properties', $property) &&
                                            $property['properties'] !== null
                                        ) {
                                            $examples[$propertyName] = $iterate($property);
                                            if (count($examples[$propertyName]) === 0) {
                                                unset($examples[$propertyName]);
                                            }
                                            continue;
                                        }

                                        if (
                                            array_key_exists('type', $property) &&
                                            $property['type'] === 'array' &&
                                            array_key_exists('items', $property) &&
                                            $property['items'] !== null &&
                                            array_key_exists('type', $property['items']) &&
                                            $property['items']['type'] === 'object'
                                        ) {
                                            $items = $iterate($property['items']);

                                            if (count($items) > 0) {
                                                $examples[$propertyName] = [$items];
                                            }
                                            continue;
                                        }

                                        if (array_key_exists('examples', $property)) {
                                            $examples[$propertyName] = $property['examples'][count($property['examples']) === 1 ? 0 : mt_rand(0, count($property['examples']) - 1)];
                                        } else if (
                                            array_key_exists('example', $property) &&
                                            $property['example'] !== null
                                        ) {
                                            $examples[$propertyName] = $property['example'];
                                        }
                                    }

                                    return $examples;
                                };

                                return $iterate($schema);
                            })(json_decode(json_encode($schema->getSerializableData()), true)))
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
        $constructor = (new BuilderFactory())->method('__construct')->makePublic();
        $constructDocBlock = [];
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
            $propertyStmt = $factory->property($propertyName)->makePublic()->makeReadonly();
            $propertyDocBlock = [];
            if (is_string($property->description) && strlen($property->description) > 0) {
                $propertyDocBlock[] = $property->description;
            }
            $propertyType = $property->type;
            $setDefaylt = true;
            $nullable = '';
            if ($property->nullable) {
                $nullable = '?';
//                $propertyStmt->setDefault(null);
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
//                    $propertyStmt->setDefault(null);
                }

                if ($propertyType === 'array'/* && $property->items instanceof OpenAPiSchema*/) {
//                    if (array_key_exists(spl_object_hash($property->items), $schemaClassNameMap)) {
                        $propertyDocBlock[] = '@var array<\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->items, $className . '\\' . (new Convert($propertyName))->toPascal()) . '>';
//                        $constructDocBlock[] = '@param array<\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->items, $className . '\\' . (new Convert($propertyName))->toPascal()) . '>';
                        $constructDocBlock[] = '@param array<\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->items, $className . '\\' . (new Convert($propertyName))->toPascal()) . '> $' . $propertyName;
//                    } elseif ($property->items->type === 'object') {
//                        $propertyDocBlock[] = '@var array<\\' . $namespace . '\\' . $className . '\\' . (new Convert($propertyName))->toPascal() . '>';
//                    }
                }

                if (is_string($propertyType)) {
                    $propertyType = str_replace([
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
                    ], $propertyType);

                    if ($propertyType === '') {
                        $propertyType = 'mixed';
                    }
                }
            } else {
                $propertyType = 'mixed';
            }


            $propertyStmt->setType(($propertyType === 'array' ? '' : $nullable) . $propertyType);
            $constructorParam = (new Param($propertyName))->setType($propertyType);
            $constructor->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $propertyName
                    ),
                    new Node\Expr\Variable($propertyName),
                )
            );

            // 74908

            if (is_array($property->anyOf) && $property->anyOf[0] instanceof OpenAPiSchema/* && array_key_exists(spl_object_hash($property->anyOf[0]), $schemaClassNameMap)*/) {
                $fqcnn = '\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->anyOf[0], $className . '\\' . (new Convert($propertyName))->toPascal());
                $propertyStmt->setType($nullable . $fqcnn);
                $constructorParam->setType($nullable . $fqcnn);
                $setDefaylt = false;
            } else if (is_array($property->allOf) && $property->allOf[0] instanceof OpenAPiSchema/* && array_key_exists(spl_object_hash($property->allOf[0]), $schemaClassNameMap)*/) {
                $fqcnn = '\\' . $rootNamespace . '\\' . $schemaRegistry->get($property->allOf[0], $className . '\\' . (new Convert($propertyName))->toPascal());
                $propertyStmt->setType($nullable . $fqcnn);
                $constructorParam->setType($nullable . $fqcnn);
                $setDefaylt = false;
            }

//            if (($property->type  === 'object' || (is_array($property->type) && count($property->type) === 2)) && $property instanceof OpenAPiSchema/* && array_key_exists(spl_object_hash($property), $schemaClassNameMap)*/) {
            if ($propertyType  === 'object') {
                $fqcnn = '\\' . $rootNamespace . '\\' . $schemaRegistry->get($property, $className . '\\' . (new Convert($propertyName))->toPascal());
                $propertyStmt->setType($nullable . $fqcnn);
                $constructorParam->setType($nullable . $fqcnn);
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
//                        $propertyStmt->setDefault([]);
                    }
                }
            }

            if (count($propertyDocBlock) > 0) {
                $propertyStmt->setDocComment('/**' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', str_replace(['/**', '*/'], '', $propertyDocBlock)) . PHP_EOL .' */');
            }

            $class->addStmt($propertyStmt);
            $constructor->addParam($constructorParam);
        }

        if (count($constructDocBlock) > 0) {
            $constructor->setDocComment('/**' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', str_replace(['/**', '*/'], '', $constructDocBlock)) . PHP_EOL .' */');
        }

        $class->addStmt($constructor);
    }
}
