<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers;
use ApiClients\Tools\OpenApiClientGenerator\PrivatePromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\PromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use Jawira\CaseConverter\Convert;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Arg;
use PhpParser\Node\Name;
use React\Http\Browser;
use function trim;

final class Operations
{
    /**
     * @param array<Operation> $operations
     *
     * @return iterable<File>
     */
    public static function generate(Configuration $configuration, string $pathPrefix, string $namespace, array $operations, Routers $routers): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim($namespace, '\\'));

        $class = $factory->class('Operations')->makeFinal()->implement(new Name('OperationsInterface'))->makeReadonly();

        $class->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new PrivatePromotedPropertyAsParam('browser'))->setType('\\' . Browser::class),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('authentication'))->setType('\\' . AuthenticationInterface::class),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('hydrators'))->setType($namespace . 'Hydrators'),
            ),
        );

        $groups = [];
        foreach ($operations as $operation) {
            $groups[$operation->group][] = $operation;
        }

        foreach ($groups as $group => $groupsOperations) {
            $class->addStmt(
                $factory->method((new Convert($group))->toCamel())->makePublic()->setReturnType('Operation\\' . $group)->addStmts([
                     new Node\Stmt\Return_(
                        new Expr\New_(
                            new Name(
                                'Operation\\' . $group
                            ),
                            [
                                new Arg(
                                    new Expr\PropertyFetch(
                                        new Expr\Variable('this'),
                                        new Name('browser')
                                    ),
                                ),
                                new Arg(
                                    new Expr\PropertyFetch(
                                        new Expr\Variable('this'),
                                        new Name('authentication')
                                    ),
                                ),
                                new Arg(
                                    new Expr\PropertyFetch(
                                        new Expr\Variable('this'),
                                        new Name('requestSchemaValidator')
                                    ),
                                ),
                                new Arg(
                                    new Expr\PropertyFetch(
                                        new Expr\Variable('this'),
                                        new Name('responseSchemaValidator')
                                    ),
                                ),
                                new Arg(
                                    new Expr\PropertyFetch(
                                        new Expr\Variable('this'),
                                        new Name('hydrators')
                                    ),
                                ),
                            ],
                        ),
                    ),
                ]),
            );

            yield from self::generateOperationsGroup(
                $pathPrefix,
                $namespace,
                'Operation\\' . $group,
                $groupsOperations,
                $group,
                $routers,
            );
        }

        yield new File($pathPrefix, 'Operations', $stmt->addStmt($class)->getNode());
    }

    /**
     * @param array<Operation> $operations
     *
     * @return iterable<File>
     */
    private static function generateOperationsGroup(string $pathPrefix, string $namespace, string $className, array $operations, string $group, Routers $routers): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim(Utils::dirname($namespace . $className), '\\'));

        $class = $factory->class(trim(Utils::basename($className), '\\'))->makeFinal()->addStmt(
            $factory->property('router')->setType('array')->setDefault([])->makePrivate(),
        );

        $class->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new PrivatePromotedPropertyAsParam('browser'))->setType('\\' . Browser::class),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('authentication'))->setType('\\' . AuthenticationInterface::class),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('hydrators'))->setType($namespace . 'Hydrators'),
            ),
        );

        foreach ($routers->get() as $router) {
            if ($router->group !== $group) {
                continue;
            }

            $routerClassName = $routers->createClassName($router->method, $router->group, '')->class;
            foreach ($router->methods as $method) {
                $class->addStmt(
                    $factory->method((new Convert($method->name))->toCamel())->makePublic()->addParam(
                        (new Param('params'))->setType('array')->setDefault([])
                    )->addStmts([
                        new Node\Stmt\If_(
                            new Node\Expr\BinaryOp\Equal(
                                new Node\Expr\FuncCall(
                                    new Node\Name('\array_key_exists'),
                                    [
                                        new Arg(new Node\Expr\ClassConstFetch(
                                            new Node\Name($routerClassName),
                                            new Node\Name('class'),
                                        )),
                                        new Arg(new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            'router'
                                        )),
                                    ],
                                ),
                                new Node\Expr\ConstFetch(new Node\Name('false'))
                            ),
                            [
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                'router'
                                            ), new Node\Expr\ClassConstFetch(
                                                new Node\Name($routerClassName),
                                                new Node\Name('class'),
                                            )),
                                            new Node\Expr\New_(
                                                new Node\Name($routerClassName),
                                                [
                                                    new Arg(new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'requestSchemaValidator'
                                                    )),
                                                    new Arg(new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'responseSchemaValidator'
                                                    )),
                                                    new Arg(new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'hydrators'
                                                    )),
                                                    new Arg(new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'browser'
                                                    )),
                                                    new Arg(new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'authentication'
                                                    )),
                                                ],
                                            ),
                                        ),
                                    ),
                                ],
                            ],
                        ),
                        new Node\Stmt\Return_(
                            new Expr\MethodCall(
                                new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'router'
                                ), new Node\Expr\ClassConstFetch(
                                    new Node\Name($routerClassName),
                                    new Node\Name('class'),
                                )),
                                new Node\Name(
                                    'call',
                                ),
                                [
                                    new Arg(
                                        new Expr\Variable('params'),
                                    ),
                                ],
                            ),
                        ),
                    ]),
                );
            }
        }
//        $groups = [];
//        foreach ($operations as $operation) {
//            $groups[$operation->group][] = $operation;
//        }
//
//        foreach ($groups as $group => $groupsOperations) {
//            $class->addStmt(
//                $factory->method((new Convert($group))->toCamel())->makePublic()->setReturnType('Operation\\' . $group),
//            );
//
//            yield from self::generateOperationsGroup(
//                $pathPrefix,
//                $namespace,
//                'Operation\\' . $group,
//                $groupsOperations,
//            );
//        }

        yield new File($pathPrefix, $className, $stmt->addStmt($class)->getNode());
    }
}
