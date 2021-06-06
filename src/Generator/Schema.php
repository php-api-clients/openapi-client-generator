<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
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
    public static function generate(string $name, string $namespace, string $className, OpenAPiSchema $schema, array $schemaClassNameMap, string $rootNamespace): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal()->addStmt(
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
            $setDefaylt = true;
            if (is_string($property->type)) {
                if ($property->type === 'array' && $property->items instanceof OpenAPiSchema) {
                    if (array_key_exists(spl_object_hash($property->items), $schemaClassNameMap)) {
                        $methodDocBlock[] = '@return array<\\' . $rootNamespace . '\\' . $schemaClassNameMap[spl_object_hash($property->items)] . '>';
                        $propertyDocBlock[] = '@var array<\\' . $rootNamespace . '\\' . $schemaClassNameMap[spl_object_hash($property->items)] . '>';
                        $propertyDocBlock[] = '@\WyriHaximus\Hydrator\Attribute\HydrateArray(\\' . $rootNamespace . '\\' . $schemaClassNameMap[spl_object_hash($property->items)] . '::class)';
                    } elseif ($property->items->type === 'object') {
                        yield from self::generate($name . '::' . $propertyName, $namespace . '\\' . $className, (new Convert($propertyName))->toPascal(), $property->items, $schemaClassNameMap, $rootNamespace);
                        $methodDocBlock[] = '@return array<\\' . $namespace . '\\' . $className . '\\' . (new Convert($propertyName))->toPascal() . '>';
                        $propertyDocBlock[] = '@var array<\\' . $namespace . '\\' . $className . '\\' . (new Convert($propertyName))->toPascal() . '>';
                        $propertyDocBlock[] = '@\WyriHaximus\Hydrator\Attribute\HydrateArray(\\' . $namespace . '\\' . $className . '\\' . (new Convert($propertyName))->toPascal() . '::class)';
                    }
                }


                if (is_string($property->type)) {
                    $t = str_replace([
                        'object',
                        'integer',
                        'any',
                        'null',
                        'boolean',
                    ], [
                        'array',
                        'int',
                        '',
                        '',
                        'bool',
                    ], $property->type);
                    if ($t !== '') {
                        $propertyStmt->setType($t);
                        $method->setReturnType($t);
                    }
                }

            }

            if (is_array($property->anyOf) && $property->anyOf[0] instanceof OpenAPiSchema && array_key_exists(spl_object_hash($property->anyOf[0]), $schemaClassNameMap)) {
                $fqcnn = '\\' . $rootNamespace . '\\' . $schemaClassNameMap[spl_object_hash($property->anyOf[0])];
                $propertyStmt->setType( $fqcnn);
                $method->setReturnType( $fqcnn);
                $propertyDocBlock[] = '@\WyriHaximus\Hydrator\Attribute\Hydrate(' . $fqcnn . '::class)';
                $setDefaylt = false;
            }

            if ($property->type  === 'object' && $property instanceof OpenAPiSchema && array_key_exists(spl_object_hash($property), $schemaClassNameMap)) {
                $fqcnn = '\\' . $rootNamespace . '\\' . $schemaClassNameMap[spl_object_hash($property)];
                $propertyStmt->setType( $fqcnn);
                $method->setReturnType( $fqcnn);
                $propertyDocBlock[] = '@\WyriHaximus\Hydrator\Attribute\Hydrate(' . $fqcnn . '::class)';
                $setDefaylt = false;
            }

            if (is_string($property->type)) {
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
                ], $property->type);
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

        yield new File($namespace . '\\' . $className, $stmt->addStmt($class)->getNode());
    }
}
