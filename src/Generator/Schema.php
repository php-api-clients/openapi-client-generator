<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use cebe\openapi\spec\Schema as OpenAPiSchema;
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
     * @param OpenAPiSchema $operation
     * @return iterable<Node>
     */
    public static function generate(string $name, string $namespace, string $className, OpenAPiSchema $operation): Node
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal()->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_TITLE',
                        new Node\Scalar\String_(
                            $operation->title ?? $name
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
                            $operation->description ?? ''
                        )
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        );

        foreach ($operation->properties as $propertyName => $property) {
            $propertyStmt = $factory->property($propertyName)->makePrivate();
            if (strlen($property->description) > 0) {
                $propertyStmt->setDocComment('/**' . $property->description . '**/');
            }
            $method = $factory->method($propertyName)->makePublic()/*->setReturnType('string')*/->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $propertyName
                    )
                )
            );
            if (is_string($property->type)) {
                $propertyStmt->setType(str_replace([
                    'integer',
                    'any',
                ], [
                    'int',
                    '',
                ], $property->type));
                $method->setReturnType(str_replace([
                    'integer',
                    'any',
                ], [
                    'int',
                    '',
                ], $property->type));
            }
            $class->addStmt($propertyStmt)->addStmt($method);

            $param = (new Param(
                $propertyName
            ))/*->setType(
                str_replace([
                    'integer',
                    'any',
                ], [
                    'int',
                    '',
                ], $property->type)
            )*/;
            if ($property->default !== null) {
                $param->setDefault($property->default);
            }
        }

        return $stmt->addStmt($class)->getNode();
    }
}
