<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Schema as OpenAPiSchema;
use Jawira\CaseConverter\Convert;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use RingCentral\Psr7\Request;

final class Schema
{
    /**
     * @param string $name
     * @param string $namespace
     * @param string $schema->className
     * @param OpenAPiSchema $schema
     * @return iterable<Node>
     */
    public static function generate(string $namespace, \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema $schema, SchemaRegistry $schemaRegistry): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace(trim(Utils::dirname($namespace . '\\Schema\\' . $schema->className), '\\'));

        $schemaJson = new Node\Stmt\ClassConst(
            [
                new Node\Const_(
                    'SCHEMA_JSON',
                    new Node\Scalar\String_(
                        json_encode($schema->schema->getSerializableData())
                    )
                ),
            ],
            Class_::MODIFIER_PUBLIC
        );

        $class = $factory->class(trim(Utils::basename($schema->className), '\\'))->makeFinal()->makeReadonly()->addStmt(
            $schemaJson
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_TITLE',
                        new Node\Scalar\String_(
                            $schema->title
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
                            $schema->description
                        )
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_EXAMPLE_DATA',
                        $factory->val(json_encode($schema->example)),
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        );

        $constructor = (new BuilderFactory())->method('__construct')->makePublic();
        $constructDocBlock = [];
        foreach ($schema->properties as $property) {
            $propertyStmt = $factory->property($property->name)->makePublic();
            $propertyDocBlock = [];
            if (is_string($property->description) && strlen($property->description) > 0) {
                $propertyDocBlock[] = $property->description;
            }

            $nullable = '';
            if ($property->nullable) {
                $nullable = '?';
            }

            $types = [];
            foreach ($property->type as $type) {
                if ($type->type === 'array') {
                    $propertyDocBlock[] = '@var array<' . ($type->payload->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema ? 'Schema\\' . $type->payload->payload->className : $type->payload->payload) . '>';
                    $types[] = 'array';
                    continue;
                }

                if ($type->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
                    $types[] = 'Schema\\' . $type->payload->className;
                    continue;
                }

                $types[] = $type->payload;
            }

            $propertyStmt->setType(($property->type === 'array' ? '' : $nullable) . implode('|', $types));
            $constructorParam = (new Param($property->name))->setType(implode('|', $types));
            $constructor->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $property->name
                    ),
                    new Node\Expr\Variable($property->name),
                )
            );

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


        yield new File($namespace . 'Schema\\' . $schema->className, $stmt->addStmt($class)->getNode());
    }
}
