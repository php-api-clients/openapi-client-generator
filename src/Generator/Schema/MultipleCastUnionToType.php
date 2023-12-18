<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Schema;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;

final class MultipleCastUnionToType
{
    /** @return iterable<File> */
    public static function generate(string $pathPrefix, ClassString $classString, ClassString $wrappingClassString, Schema ...$schemas): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($classString->namespace->source);

        $class = $factory->class($classString->className)->makeFinal()->makeReadonly()->addAttribute(
            new Node\Attribute(
                new Node\Name('\\' . Attribute::class),
                [
                    new Node\Arg(
                        new Node\Expr\ClassConstFetch(
                            new Node\Name('\\' . Attribute::class),
                            'TARGET_PARAMETER',
                        ),
                    ),
                ],
            ),
        )->implement('\\' . PropertyCaster::class)->addStmt(
            $factory->property('wrappedCaster')->makePrivate()->setType($wrappingClassString->fullyQualified->source),
        )->addStmt(
            $factory->method('__construct')->makePublic()->addStmts([
                new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable(
                                new Node\Name('this'),
                            ),
                            new Node\Name('wrappedCaster'),
                        ),
                        new Node\Expr\New_(
                            new Node\Name(
                                $wrappingClassString->fullyQualified->source,
                            ),
                        ),
                    ),
                ),
            ]),
        )->addStmt(
            $factory->method('cast')->makePublic()->addParams([
                (new Param('value'))->setType('mixed'),
                (new Param('hydrator'))->setType('\\' . ObjectMapper::class),
            ])->setReturnType('mixed')->addStmts([
                new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\Variable(
                            new Node\Name('data'),
                        ),
                        new Node\Expr\Array_(),
                    ),
                ),
                new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\Variable(
                            new Node\Name('values'),
                        ),
                        new Node\Expr\Variable(
                            new Node\Name('value'),
                        ),
                    ),
                ),
                new Node\Expr\FuncCall(
                    new Node\Name('unset'),
                    [
                        new Node\Arg(
                            new Node\Expr\Variable(
                                new Node\Name('value'),
                            ),
                        ),
                    ],
                ),
                new Node\Stmt\Foreach_(
                    new Node\Expr\Variable(
                        new Node\Name('values'),
                    ),
                    new Node\Expr\Variable(
                        new Node\Name('value'),
                    ),
                    [
                        'stmts' => [
                            new Node\Stmt\Expression(
                                new Node\Expr\Assign(
                                    new Node\Expr\ArrayDimFetch(
                                        new Node\Expr\Variable(
                                            new Node\Name('values'),
                                        ),
                                    ),
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable(
                                                new Node\Name('this'),
                                            ),
                                            new Node\Name('wrappedCaster'),
                                        ),
                                        new Node\Name('cast'),
                                        [
                                            new Node\Arg(
                                                new Node\Expr\Variable(
                                                    new Node\Name('value'),
                                                ),
                                            ),
                                            new Node\Arg(
                                                new Node\Expr\Variable(
                                                    new Node\Name('hydrator'),
                                                ),
                                            ),
                                        ],
                                    ),
                                ),
                            ),
                        ],
                    ],
                ),
                new Node\Stmt\Return_(
                    new Node\Expr\Variable('data'),
                ),
            ]),
        );

        yield new File($pathPrefix, $classString->relative, $stmt->addStmt($class)->getNode());
    }
}
