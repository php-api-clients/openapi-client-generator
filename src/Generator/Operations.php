<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\PrivatePromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use Jawira\CaseConverter\Convert;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use React\Http\Browser;
use React\Promise\PromiseInterface;

use function count;
use function ucfirst;

final class Operations
{
    /**
     * @param array<Path>      $paths
     * @param array<Operation> $operations
     *
     * @return iterable<File>
     */
    public static function generate(Configuration $configuration, string $pathPrefix, array $paths, array $operations): iterable
    {
        $operationHydratorMap = [];
        foreach ($paths as $path) {
            foreach ($path->operations as $pathOperation) {
                $operationHydratorMap[$pathOperation->operationId] = $path->hydrator;
            }
        }

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($configuration->namespace->source);

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
                (new PrivatePromotedPropertyAsParam('hydrators'))->setType('\\' . $configuration->namespace->source . '\Hydrators'),
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
                                        'browser',
                                    ),
                                ),
                                new Arg(
                                    new Expr\PropertyFetch(
                                        new Expr\Variable('this'),
                                        'authentication',
                                    ),
                                ),
                                new Arg(
                                    new Expr\PropertyFetch(
                                        new Expr\Variable('this'),
                                        'requestSchemaValidator',
                                    ),
                                ),
                                new Arg(
                                    new Expr\PropertyFetch(
                                        new Expr\Variable('this'),
                                        'responseSchemaValidator',
                                    ),
                                ),
                                new Arg(
                                    new Expr\PropertyFetch(
                                        new Expr\Variable('this'),
                                        'hydrators',
                                    ),
                                ),
                            ],
                        ),
                    ),
                ]),
            );

            yield from self::generateOperationsGroup(
                $pathPrefix,
                $configuration->namespace,
                'Operation\\' . $group,
                $groupsOperations,
                $operationHydratorMap,
                $group,
            );
        }

        yield new File($pathPrefix, 'Operations', $stmt->addStmt($class)->getNode());
    }

    /**
     * @param array<string, Hydrator> $operationHydratorMap
     * @param array<Operation>        $operations
     *
     * @return iterable<File>
     */
    private static function generateOperationsGroup(string $pathPrefix, Configuration\Namespace_ $namespace, string $className, array $operations, array $operationHydratorMap, string $group): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(Utils::dirname($namespace->source . '\\' . $className));

        $class = $factory->class(Utils::basename($className))->makeFinal()->addStmt(
            $factory->property('operator')->setType('array')->setDefault([])->makePrivate(),
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
                (new PrivatePromotedPropertyAsParam('hydrators'))->setType('\\' . $namespace->source . '\Hydrators'),
            ),
        );

        foreach ($operations as $operation) {
            if ($operation->group !== $group) {
                continue;
            }

            $class->addStmt(
                $factory->method((new Convert($operation->name))->toCamel())->makePublic()->setReturnType('\\' . PromiseInterface::class)->addParams([
                    ...(static function (array $params): iterable {
                        foreach ($params as $param) {
                            yield (new Param($param->targetName))->setType($param->type === '' ? 'mixed' : $param->type);
                        }
                    })($operation->parameters),
                    ...(count($operation->requestBody) > 0 ? [
                        (new Param('params'))->setType('array'),
                    ] : []),
                ])->addStmts([
                    new Node\Stmt\If_(
                        new Node\Expr\BinaryOp\Equal(
                            new Node\Expr\FuncCall(
                                new Node\Name('\array_key_exists'),
                                [
                                    new Arg(new Node\Expr\ClassConstFetch(
                                        new Node\Name($operation->operatorClassName->relative),
                                        'class',
                                    )),
                                    new Arg(new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        'operator'
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
                                            'operator'
                                        ), new Node\Expr\ClassConstFetch(
                                            new Node\Name($operation->operatorClassName->relative),
                                            'class',
                                        )),
                                        new Node\Expr\New_(
                                            new Node\Name($operation->operatorClassName->relative),
                                            [
                                                new Arg(new Node\Expr\PropertyFetch(
                                                    new Node\Expr\Variable('this'),
                                                    'browser'
                                                )),
                                                new Arg(new Node\Expr\PropertyFetch(
                                                    new Node\Expr\Variable('this'),
                                                    'authentication'
                                                )),
                                                ...(count($operation->requestBody) > 0 ? [
                                                    new Arg(new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'requestSchemaValidator'
                                                    )),
                                                ] : []),
                                                new Arg(new Node\Expr\PropertyFetch(
                                                    new Node\Expr\Variable('this'),
                                                    'responseSchemaValidator'
                                                )),
                                                new Arg(
                                                    new Expr\MethodCall(
                                                        new Node\Expr\PropertyFetch(
                                                            new Node\Expr\Variable('this'),
                                                            'hydrators'
                                                        ),
                                                        'getObjectMapper' . ucfirst($operationHydratorMap[$operation->operationId]->methodName),
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
                        new Expr\MethodCall(
                            new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'operator'
                            ), new Node\Expr\ClassConstFetch(
                                new Node\Name($operation->operatorClassName->relative),
                                'class',
                            )),
                            'call',
                            [
                                ...(static function (array $params): iterable {
                                    foreach ($params as $param) {
                                        yield new Arg(new Node\Expr\Variable($param->targetName));
                                    }
                                })($operation->parameters),
                                ...(count($operation->requestBody) > 0 ? [new Arg(new Node\Expr\Variable('params'))] : []),
                            ],
                        ),
                    ),
                ]),
            );
        }

        yield new File($pathPrefix, $className, $stmt->addStmt($class)->getNode());
    }
}
