<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use EventSauce\ObjectHydrator\IterableList;
use EventSauce\ObjectHydrator\ObjectMapper;
use Generator;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;

use function array_filter;
use function array_key_exists;
use function array_map;
use function count;
use function trim;
use function ucfirst;

final class Hydrators
{
    /** @return iterable<File> */
    public static function generate(string $pathPrefix, string $namespace, Hydrator ...$hydrators): iterable
    {
        $knownScehmas = [];
        $factory      = new BuilderFactory();
        $stmt         = $factory->namespace(trim($namespace, '\\') . '\\Internal');

        $class = $factory->class('Hydrators')->makeFinal()->implement('\\' . ObjectMapper::class);

        $usefullHydrators = [];
        foreach ($hydrators as $hydrator) {
            $usefullHydrators[$hydrator->className->relative] = array_filter($hydrator->schemas, static function (Schema $schema) use (&$knownScehmas): bool {
                if (array_key_exists($schema->className->relative, $knownScehmas)) {
                    return false;
                }

                $knownScehmas[$schema->className->relative] = $schema->className;

                return true;
            });
        }

        $matchHydrators = array_filter($hydrators, static fn (Hydrator $hydrator): bool => count($usefullHydrators[$hydrator->className->relative]) > 0);

        foreach ($hydrators as $hydrator) {
            $class->addStmt($factory->property($hydrator->methodName)->setType('?' . $hydrator->className->relative)->setDefault(null)->makePrivate());
        }

        $class->addStmt(
            $factory->method('hydrateObject')->makePublic()->setReturnType('object')->addParams([
                (new Param('className'))->setType('string'),
                (new Param('payload'))->setType('array'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\Match_(
                        new Node\Expr\Variable('className'),
                        array_map(static fn (Hydrator $hydrator): Node\MatchArm => new Node\MatchArm(
                            array_map(static fn (Schema $schema): Node\Scalar\String_ => new Node\Scalar\String_(
                                $schema->className->fullyQualified->source,
                            ), $usefullHydrators[$hydrator->className->relative]),
                            new Node\Expr\MethodCall(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('this'),
                                    'getObjectMapper' . ucfirst($hydrator->methodName),
                                ),
                                'hydrateObject',
                                [
                                    new Node\Arg(
                                        new Node\Expr\Variable('className'),
                                    ),
                                    new Node\Arg(
                                        new Node\Expr\Variable('payload'),
                                    ),
                                ],
                            ),
                        ), $matchHydrators),
                    ),
                ),
            ),
        );

        $class->addStmt(
            $factory->method('hydrateObjects')->makePublic()->setReturnType('\\' . IterableList::class)->addParams([
                (new Param('className'))->setType('string'),
                (new Param('payloads'))->setType('iterable'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\New_(
                        new Node\Name('\\' . IterableList::class),
                        [
                            new Node\Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('this'),
                                    'doHydrateObjects',
                                    [
                                        new Node\Arg(
                                            new Node\Expr\Variable('className'),
                                        ),
                                        new Node\Arg(
                                            new Node\Expr\Variable('payloads'),
                                        ),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ),
        );

        $class->addStmt(
            $factory->method('doHydrateObjects')->makePrivate()->setReturnType('\\' . Generator::class)->addParams([
                (new Param('className'))->setType('string'),
                (new Param('payloads'))->setType('iterable'),
            ])->addStmt(
                new Node\Stmt\Foreach_(
                    new Node\Expr\Variable('payloads'),
                    new Node\Expr\Variable('payload'),
                    [
                        'keyVar' => new Node\Expr\Variable('index'),
                        'stmts' => [
                            new Node\Stmt\Expression(
                                new Node\Expr\Yield_(
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\Variable('this'),
                                        'hydrateObject',
                                        [
                                            new Node\Arg(
                                                new Node\Expr\Variable('className'),
                                            ),
                                            new Node\Arg(
                                                new Node\Expr\Variable('payload'),
                                            ),
                                        ],
                                    ),
                                    new Node\Expr\Variable('index'),
                                ),
                            ),
                        ],
                    ],
                ),
            ),
        );

        $class->addStmt(
            $factory->method('serializeObject')->makePublic()->setReturnType('mixed')->addParams([
                (new Param('object'))->setType('object'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable('this'),
                        'serializeObjectOfType',
                        [
                            new Node\Arg(
                                new Node\Expr\Variable('object'),
                            ),
                            new Node\Arg(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Expr\Variable('object'),
                                    'class',
                                ),
                            ),
                        ],
                    ),
                ),
            ),
        );

        $class->addStmt(
            $factory->method('serializeObjectOfType')->makePublic()->setReturnType('mixed')->addParams([
                (new Param('object'))->setType('object'),
                (new Param('className'))->setType('string'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\Match_(
                        new Node\Expr\Variable('className'),
                        array_map(static fn (Hydrator $hydrator): Node\MatchArm => new Node\MatchArm(
                            array_map(static fn (Schema $schema): Node\Scalar\String_ => new Node\Scalar\String_(
                                $schema->className->fullyQualified->source,
                            ), $usefullHydrators[$hydrator->className->relative]),
                            new Node\Expr\MethodCall(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('this'),
                                    'getObjectMapper' . ucfirst($hydrator->methodName),
                                ),
                                'serializeObject',
                                [
                                    new Node\Arg(
                                        new Node\Expr\Variable('object'),
                                    ),
                                ],
                            ),
                        ), $matchHydrators),
                    ),
                ),
            ),
        );

        $class->addStmt(
            $factory->method('serializeObjects')->makePublic()->setReturnType('\\' . IterableList::class)->addParams([
                (new Param('payloads'))->setType('iterable'),
            ])->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\New_(
                        new Node\Name('\\' . IterableList::class),
                        [
                            new Node\Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('this'),
                                    'doSerializeObjects',
                                    [
                                        new Node\Arg(
                                            new Node\Expr\Variable('payloads'),
                                        ),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ),
        );

        $class->addStmt(
            $factory->method('doSerializeObjects')->makePrivate()->setReturnType('\\' . Generator::class)->addParams([
                (new Param('objects'))->setType('iterable'),
            ])->addStmt(
                new Node\Stmt\Foreach_(
                    new Node\Expr\Variable('objects'),
                    new Node\Expr\Variable('object'),
                    [
                        'keyVar' => new Node\Expr\Variable('index'),
                        'stmts' => [
                            new Node\Stmt\Expression(
                                new Node\Expr\Yield_(
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\Variable('this'),
                                        'serializeObject',
                                        [
                                            new Node\Arg(
                                                new Node\Expr\Variable('object'),
                                            ),
                                        ],
                                    ),
                                    new Node\Expr\Variable('index'),
                                ),
                            ),
                        ],
                    ],
                ),
            ),
        );

        foreach ($hydrators as $hydrator) {
            $class->addStmt(
                $factory->method('getObjectMapper' . ucfirst($hydrator->methodName))->makePublic()->setReturnType($hydrator->className->relative)->addStmts([
                    new Node\Stmt\If_(
                        new Node\Expr\BinaryOp\Identical(
                            new Node\Expr\Instanceof_(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    $hydrator->methodName,
                                ),
                                new Node\Name($hydrator->className->relative),
                            ),
                            new Node\Expr\ConstFetch(new Node\Name('false')),
                        ),
                        [
                            'stmts' => [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Assign(
                                        new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            $hydrator->methodName,
                                        ),
                                        new Node\Expr\New_(
                                            new Node\Name($hydrator->className->relative),
                                        ),
                                    ),
                                ),
                            ],
                        ],
                    ),
                    new Node\Stmt\Return_(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            $hydrator->methodName,
                        ),
                    ),
                ]),
            );
        }

        yield new File($pathPrefix, 'Internal\\Hydrators', $stmt->addStmt($class)->getNode());
    }
}
