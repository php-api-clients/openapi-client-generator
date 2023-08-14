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
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use React\Http\Browser;

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

        $classReadonly = true;
        $groups        = [];
        foreach ($operations as $operation) {
            $groups[$operation->group][] = $operation;

            if ($operation->group !== null) {
                continue;
            }

            $classReadonly = false;
        }

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($configuration->namespace->source);

        $class = $factory->class('Operations')->makeFinal()->implement(new Name('OperationsInterface'));
        if ($classReadonly) {
            $class->makeReadonly();
        } else {
            $class->addStmt(
                $factory->property('operator')->setType('array')->setDefault([])->makePrivate(),
            );
        }

        $params = [];
        foreach (
            [
                'browser' => '\\' . Browser::class,
                'authentication' => '\\' . AuthenticationInterface::class,
                'requestSchemaValidator' => '\League\OpenAPIValidation\Schema\SchemaValidator',
                'responseSchemaValidator' => '\League\OpenAPIValidation\Schema\SchemaValidator',
                'hydrators' => '\\' . $configuration->namespace->source . '\Hydrators',
            ] as $name => $type
        ) {
            if ($classReadonly) {
                $params[] = (new PrivatePromotedPropertyAsParam($name))->setType($type);

                continue;
            }

            $params[] = (new PrivatePromotedPropertyAsParam($name))->setType($type);
//            $params[] = (new PrivatePromotedPropertyAsParam($name))->setType($type)->makeReadonly();
        }

        $class->addStmt(
            $factory->method('__construct')->makePublic()->addParams($params),
        );

        foreach ($groups as $group => $groupsOperations) {
            if ($group === '') {
                foreach ($groupsOperations as $groupsOperation) {
                    $class->addStmt(
                        Helper\Operation::methodSignature(
                            $factory->method((new Convert($groupsOperation->name))->toCamel())->makePublic(),
                            $groupsOperation,
                        )->addStmts(Helper\Operation::methodCallOperation($groupsOperation, $operationHydratorMap)),
                    );
                }

                continue;
            }

            $class->addStmt(
                $factory->method((new Convert($group))->toCamel())->makePublic()->setReturnType('Operation\\' . $group)->addStmts([
                    new Node\Stmt\Return_(
                        new Expr\New_(
                            new Name(
                                'Operation\\' . $group,
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
                Helper\Operation::methodSignature(
                    $factory->method((new Convert($operation->name))->toCamel())->makePublic(),
                    $operation,
                )->addStmts(Helper\Operation::methodCallOperation($operation, $operationHydratorMap)),
            );
        }

        yield new File($pathPrefix, $className, $stmt->addStmt($class)->getNode());
    }
}
