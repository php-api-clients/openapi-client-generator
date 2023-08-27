<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\PrivatePromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use Jawira\CaseConverter\Convert;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

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

        $groups = [];
        foreach ($operations as $operation) {
            $groups[$operation->group][] = $operation;
        }

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($configuration->namespace->source);

        $class = $factory->class('Operations')->makeFinal()->implement(new Name('OperationsInterface'))->makeReadonly();

        $class->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new PrivatePromotedPropertyAsParam('operators'))->setType('Internal\\Operators'),
            ),
        );

        foreach ($groups as $group => $groupsOperations) {
            if ($group === '') {
                foreach ($groupsOperations as $groupsOperation) {
                    $class->addStmt(
                        Helper\Operation::methodSignature(
                            $factory->method((new Convert($groupsOperation->name))->toCamel())->makePublic(),
                            $groupsOperation,
                        )->addStmt(Helper\Operation::methodCallOperation($groupsOperation)),
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
                                        'operators',
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
                $group,
            );
        }

        yield from Operators::generate($configuration, $pathPrefix, $operations, $operationHydratorMap);
        yield new File($pathPrefix, 'Operations', $stmt->addStmt($class)->getNode());
    }

    /**
     * @param array<Operation>        $operations
     * @param array<string, Hydrator> $operationHydratorMap
     *
     * @return iterable<File>
     */
    private static function generateOperationsGroup(string $pathPrefix, Configuration\Namespace_ $namespace, string $className, array $operations, string $group): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(Utils::dirname($namespace->source . '\\' . $className));

        $class = $factory->class(Utils::basename($className))->makeFinal()->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new PrivatePromotedPropertyAsParam('operators'))->setType('Internal\Operators'),
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
                )->addStmt(Helper\Operation::methodCallOperation($operation)),
            );
        }

        yield new File($pathPrefix, $className, $stmt->addStmt($class)->getNode());
    }
}
