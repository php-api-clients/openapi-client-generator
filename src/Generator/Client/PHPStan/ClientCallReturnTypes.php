<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\PHPStan;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\File;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

use function trim;

final class ClientCallReturnTypes
{
    /** @return iterable<File> */
    public static function generate(Package $package, Namespaced\Client $client): iterable
    {
        /** @var array<Namespaced\Operation> $operations */
        $operations = [];
        foreach ($client->paths as $path) {
            $operations = [...$operations, ...$path->operations];
        }

        $stmts = [];
        foreach ($operations as $operation) {
            $stmts[] = new Node\Stmt\If_(
                new Expr\BinaryOp\Identical(
                    new Expr\Variable(
                        new Node\Name(
                            'call',
                        ),
                    ),
                    new Node\Scalar\String_($operation->matchMethod . ' ' . $operation->path),
                ),
                [
                    'stmts' => [
                        new Node\Stmt\Return_(
                            new Expr\MethodCall(
                                new Expr\PropertyFetch(
                                    new Expr\Variable(
                                        new Node\Name(
                                            'this',
                                        ),
                                    ),
                                    new Node\Name(
                                        'typeResolver',
                                    ),
                                ),
                                new Node\Name(
                                    'resolve',
                                ),
                                [
                                    new Arg(
                                        new Node\Scalar\String_(
                                            Operation::getDocBlockResultTypeFromOperation($operation),
                                        ),
                                    ),
                                ],
                            ),
                        ),
                    ],
                ],
            );
        }

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(new Node\Name(trim($package->namespace->source . '\PHPStan', '\\')));
        $class   = $factory->class('ClientCallReturnTypes')->makeFinal()->makeReadonly()->implement(
            new Node\Name('\\' . DynamicMethodReturnTypeExtension::class),
        )->addStmt(
            $factory->property('printer')->makePrivate()->setType('\\' . Standard::class),
        )->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                $factory->param('typeResolver')->makePrivate()->setType('\\' . TypeStringResolver::class),
            )->addStmt(
                new Node\Stmt\Expression(
                    new Expr\Assign(
                        new Expr\PropertyFetch(
                            new Expr\Variable(
                                new Node\Name(
                                    'this',
                                ),
                            ),
                            new Node\Name(
                                'printer',
                            ),
                        ),
                        new Expr\New_(
                            new Node\Name(
                                '\\' . Standard::class,
                            ),
                        ),
                    ),
                ),
            ),
        )->addStmt(
            $factory->method('getClass')->makePublic()->setReturnType('string')->addStmt(
                new Node\Stmt\Return_(
                    new Expr\ClassConstFetch(
                        new Node\Name('\\' . $package->namespace->source . '\Client'),
                        new Node\Name('class'),
                    ),
                ),
            ),
        )->addStmt(
            $factory->method('isMethodSupported')->makePublic()->setReturnType('bool')->addParam(
                new Node\Param(
                    new Expr\Variable(
                        new Node\Name(
                            'methodReflection',
                        ),
                    ),
                    null,
                    new Node\Name(
                        '\\' . MethodReflection::class,
                    ),
                ),
            )->addStmt(
                new Node\Stmt\Return_(
                    new Expr\BinaryOp\Identical(
                        new Expr\MethodCall(
                            new Expr\Variable(
                                new Node\Name(
                                    'methodReflection',
                                ),
                            ),
                            new Node\Name(
                                'getName',
                            ),
                        ),
                        new Node\Scalar\String_('call'),
                    ),
                ),
            ),
        )->addStmt(
            $factory->method('getTypeFromMethodCall')->makePublic()->setReturnType(
                new Node\UnionType([
                    new Node\Name('null'),
                    new Node\Name('\\' . Type::class),
                ]),
            )->addParam(
                new Node\Param(
                    new Expr\Variable(
                        new Node\Name(
                            'methodReflection',
                        ),
                    ),
                    null,
                    new Node\Name(
                        '\\' . MethodReflection::class,
                    ),
                ),
            )->addParam(
                new Node\Param(
                    new Expr\Variable(
                        new Node\Name(
                            'methodCall',
                        ),
                    ),
                    null,
                    new Node\Name(
                        '\\' . MethodCall::class,
                    ),
                ),
            )->addParam(
                new Node\Param(
                    new Expr\Variable(
                        new Node\Name(
                            'scope',
                        ),
                    ),
                    null,
                    new Node\Name(
                        '\\' . Scope::class,
                    ),
                ),
            )->addStmt(
                new Node\Stmt\Expression(
                    new Expr\Assign(
                        new Expr\Variable(
                            new Node\Name(
                                'args',
                            ),
                        ),
                        new Expr\MethodCall(
                            new Expr\Variable(
                                new Node\Name(
                                    'methodCall',
                                ),
                            ),
                            new Node\Name(
                                'getArgs',
                            ),
                        ),
                    ),
                ),
            )->addStmt(
                new Node\Stmt\If_(
                    new Expr\BinaryOp\Identical(
                        new Expr\FuncCall(
                            new Node\Name(
                                'count',
                            ),
                            [
                                new Arg(
                                    new Expr\Variable(
                                        new Node\Name(
                                            'args',
                                        ),
                                    ),
                                ),
                            ],
                        ),
                        new Node\Scalar\LNumber(0),
                    ),
                    [
                        'stmts' => [
                            new Node\Stmt\Return_(
                                new Expr\ConstFetch(
                                    new Node\Name('null'),
                                ),
                            ),
                        ],
                    ],
                ),
            )->addStmt(
                new Node\Stmt\Expression(
                    new Expr\Assign(
                        new Expr\Variable(
                            new Node\Name(
                                'call',
                            ),
                        ),
                        new Expr\FuncCall(
                            new Node\Name(
                                'substr',
                            ),
                            [
                                new Arg(
                                    new MethodCall(
                                        new Expr\PropertyFetch(
                                            new Expr\Variable(
                                                new Node\Name(
                                                    'this',
                                                ),
                                            ),
                                            new Node\Name(
                                                'printer',
                                            ),
                                        ),
                                        new Node\Name(
                                            'prettyPrintExpr',
                                        ),
                                        [
                                            new Arg(
                                                new Expr\PropertyFetch(
                                                    new Expr\ArrayDimFetch(
                                                        new Expr\Variable(
                                                            new Node\Name(
                                                                'args',
                                                            ),
                                                        ),
                                                        new Node\Scalar\LNumber(0),
                                                    ),
                                                    new Node\Name(
                                                        'value',
                                                    ),
                                                ),
                                            ),
                                        ],
                                    ),
                                ),
                                new Arg(
                                    new Node\Scalar\LNumber(1),
                                ),
                                new Arg(
                                    new Node\Scalar\LNumber(-1),
                                ),
                            ],
                        ),
                    ),
                ),
            )->addStmts($stmts)->addStmt(
                new Node\Stmt\Return_(
                    new Expr\ConstFetch(
                        new Node\Name('null'),
                    ),
                ),
            ),
        );

        yield new File($package->destination->source, 'PHPStan\\ClientCallReturnTypes', $stmt->addStmt($class)->getNode(), File::DO_NOT_LOAD_ON_WRITE);
    }
}
