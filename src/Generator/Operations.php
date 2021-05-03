<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use cebe\openapi\spec\Operation as OpenAPiOperation;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use RingCentral\Psr7\Request;

final class Operation
{
    public static function generate(string $path, string $method, string $namespace, string $className, OpenAPiOperation $operation): Node
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal()->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'OPERATION_ID',
                        new Node\Scalar\String_(
                            $operation->operationId
                        )
                    ),
                ],
                Class_::MODIFIER_PRIVATE
            )
        )->addStmt(
            $factory->method('operationId')->makePublic()->setReturnType('string')->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\ClassConstFetch(
                        new Node\Name('self'),
                        'OPERATION_ID'
                    )
                )
            )
//        )->setDocComment('/**' . var_export($operation, true) . '**/');
        );

        $constructor = $factory->method('__construct');
        $requestReplaces = [];
        $query = [];
        foreach ($operation->parameters as $parameter) {
            $class->addStmt(
                $factory->
                property($parameter->name)->
                setDocComment('/**' . (string)$parameter->description . '**/')->
                setType(str_replace([
                    'integer',
                    'any',
                    'boolean',
                ], [
                    'int',
                    '',
                    'bool',
                ], $parameter->schema->type))
            );

            $param = (new Param(
                $parameter->name
            ))->setType(
                str_replace([
                    'integer',
                    'any',
                    'boolean',
                ], [
                    'int',
                    '',
                    'bool',
                ], $parameter->schema->type)
            );
            if ($parameter->schema->default !== null) {
                $param->setDefault($parameter->schema->default);
            }
            $constructor->addParam(
                $param
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $parameter->name
                    ),
                    new Node\Expr\Variable($parameter->name),
                )
            );
            if ($parameter->in === 'path' || $parameter->in === 'query') {
                $requestReplaces['{' . $parameter->name . '}'] = new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    $parameter->name
                );
            }
            if ($parameter->in === 'query') {
                $query[] = $parameter->name . '={' . $parameter->name . '}';
            }
        }
        $class->addStmt($constructor);
        $class->addStmt(
            $factory->method('createRequest')->setReturnType('\\' . RequestInterface::class)->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\New_(
                        new Node\Name(
                            '\\' . Request::class
                        ),
                        [
                            new Node\Arg(new Node\Scalar\String_($method)),
                            new Node\Arg(new Node\Expr\FuncCall(
                                new Node\Name('\str_replace'),
                                [
                                    new Node\Expr\Array_(array_map(static fn (string $key): Node\Expr\ArrayItem => new Node\Expr\ArrayItem(new Node\Scalar\String_($key)), array_keys($requestReplaces))),
                                    new Node\Expr\Array_(array_values($requestReplaces)),
                                    new Node\Scalar\String_($path . '?' . implode('&', $query)),
                                ]
                            )),
                        ]
                    )
                )
            )
        );
        $class->addStmt(
            $factory->method('validateResponse')
        );

        return $stmt->addStmt($class)->getNode();
    }
}
