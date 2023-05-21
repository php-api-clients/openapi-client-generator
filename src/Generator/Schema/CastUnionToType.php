<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Schema;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Property;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use Throwable;

use function array_shift;
use function count;
use function implode;

final class CastUnionToType
{
    /**
     * @return iterable<File>
     */
    public static function generate(string $pathPrefix, ClassString $classString, Schema ...$schemas): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($classString->namespace->source);

        $class = $factory->class($classString->className)->makeFinal()->addAttribute(
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
            (new BuilderFactory())->method('cast')->makePublic()->addParams([
                (new Param('value'))->setType('mixed'),
                (new Param('hydrator'))->setType('\\' . ObjectMapper::class),
            ])->setReturnType('mixed')->addStmts([
                new Node\Stmt\If_(
                    new Node\Expr\FuncCall(
                        new Node\Name('\is_array'),
                        [
                            new Node\Arg(
                                new Node\Expr\Variable('value'),
                            ),
                        ],
                    ),
                    [
                        'stmts' => [
                            new Node\Stmt\Expression(
                                new Node\Expr\Assign(
                                    new Node\Expr\Variable('signatureChunks'),
                                    new Node\Expr\FuncCall(
                                        new Node\Name('\array_unique'),
                                        [
                                            new Node\Arg(
                                                new Node\Expr\FuncCall(
                                                    new Node\Name('\array_keys'),
                                                    [
                                                        new Node\Arg(
                                                            new Node\Expr\Variable('value'),
                                                        ),
                                                    ],
                                                ),
                                            ),
                                        ],
                                    ),
                                ),
                            ),
                            new Node\Stmt\Expression(
                                new Node\Expr\FuncCall(
                                    new Node\Name('\sort'),
                                    [
                                        new Node\Arg(
                                            new Node\Expr\Variable('signatureChunks'),
                                        ),
                                    ],
                                ),
                            ),
                            new Node\Stmt\Expression(
                                new Node\Expr\Assign(
                                    new Node\Expr\Variable('signature'),
                                    new Node\Expr\FuncCall(
                                        new Node\Name('\implode'),
                                        [
                                            new Node\Arg(
                                                new Node\Scalar\String_('|'),
                                            ),
                                            new Node\Arg(
                                                new Node\Expr\Variable('signatureChunks'),
                                            ),
                                        ],
                                    ),
                                ),
                            ),
                            ...(static function (Schema ...$schemas): iterable {
                                foreach ($schemas as $schema) {
                                    $condition = new Node\Expr\BinaryOp\Identical(
                                        new Node\Expr\Variable('signature'),
                                        new Node\Scalar\String_(
                                            implode(
                                                '|',
                                                [
                                                    ...(static function (Property ...$properties): iterable {
                                                        foreach ($properties as $property) {
                                                            yield $property->sourceName;
                                                        }
                                                    })(...$schema->properties),
                                                ],
                                            ),
                                        ),
                                    );
                                    foreach ($schema->properties as $property) {
                                        $enumConditionals = [];
                                        foreach ($property->enum as $enumPossibility) {
                                            $enumConditionals[] = new Node\Expr\BinaryOp\Identical(
                                                new Node\Expr\ArrayDimFetch(
                                                    new Node\Expr\Variable('value'),
                                                    new Node\Scalar\String_($property->sourceName),
                                                ),
                                                new Node\Scalar\String_($enumPossibility),
                                            );
                                        }

                                        if (count($enumConditionals) <= 0) {
                                            continue;
                                        }

                                        $enumCondition = array_shift($enumConditionals);
                                        foreach ($enumConditionals as $enumConditional) {
                                            $enumCondition = new Node\Expr\BinaryOp\BooleanOr(
                                                $enumCondition,
                                                $enumConditional,
                                            );
                                        }

                                        $condition = new Node\Expr\BinaryOp\BooleanAnd(
                                            $condition,
                                            $enumCondition,
                                        );
                                    }

                                    yield new Node\Stmt\If_(
                                        $condition,
                                        [
                                            'stmts' => [
                                                new Node\Stmt\TryCatch([
                                                    new Node\Stmt\Return_(
                                                        new Node\Expr\MethodCall(
                                                            new Node\Expr\Variable('hydrator'),
                                                            'hydrateObject',
                                                            [
                                                                new Node\Arg(
                                                                    new Node\Expr\ClassConstFetch(
                                                                        new Node\Name($schema->className->relative),
                                                                        'class',
                                                                    ),
                                                                ),
                                                                new Node\Arg(
                                                                    new Node\Expr\Variable('value'),
                                                                ),
                                                            ],
                                                        ),
                                                    ),
                                                ], [
                                                    new Node\Stmt\Catch_(
                                                        [new Node\Name('\\' . Throwable::class)],
                                                    ),
                                                ]),
                                            ],
                                        ],
                                    );
                                }
                            })(...$schemas),
                        ],
                    ],
                ),
                new Node\Stmt\Return_(
                    new Node\Expr\Variable('value'),
                ),
            ])
        );

        yield new File($pathPrefix, $classString->relative, $stmt->addStmt($class)->getNode());
    }
}
