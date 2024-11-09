<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Paths;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;
use Jawira\CaseConverter\Convert;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\File;
use OpenAPITools\Utils\Utils;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

final class Operations
{
    public function __construct(
        private BuilderFactory $builderFactory,
    ) {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Namespaced\Representation $representation): iterable
    {
        $groups               = [];
        $operationHydratorMap = [];
        foreach ($representation->client->paths as $path) {
            foreach ($path->operations as $operation) {
                $operationHydratorMap[$operation->operationId] = $path->hydrator;
                $groups[$operation->group][]                   = $operation;
            }
        }

        $stmt = $this->builderFactory->namespace($package->namespace->source);

        $class = $this->builderFactory->class('Operations')->makeFinal()->implement(new Name('OperationsInterface'))->makeReadonly();

        $class->addStmt(
            $this->builderFactory->method('__construct')->makePublic()->addParam(
                $this->builderFactory->param('operators')->makePublic()->setType('\\' . $package->namespace->source . '\\Internal\\Operators'),
            ),
        );

        foreach ($groups as $group => $groupsOperations) {
            if ($group === '') {
                foreach ($groupsOperations as $groupsOperation) {
                    $class->addStmt(
                        Helper\Operation::methodSignature(
                            $this->builderFactory->method((new Convert($groupsOperation->name))->toCamel())->makePublic(),
                            $groupsOperation,
                        )->addStmt(Helper\Operation::methodCallOperation($groupsOperation)),
                    );
                }

                continue;
            }

            $class->addStmt(
                $this->builderFactory->method((new Convert($group))->toCamel())->makePublic()->setReturnType('Operation\\' . $group)->addStmts([
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

            yield from $this->generateOperationsGroup(
                $package,
                'Operation\\' . $group,
                $groupsOperations,
                $group,
            );
        }

        yield new File($package->destination->source, 'Operations', $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }

    /**
     * @param array<Namespaced\Operation>        $operations
     * @param array<string, Namespaced\Hydrator> $operationHydratorMap
     *
     * @return iterable<File>
     */
    private function generateOperationsGroup(Package $package, string $className, array $operations, string $group): iterable
    {
        $stmt = $this->builderFactory->namespace(Utils::dirname($package->namespace->source . '\\' . $className));

        $class = $this->builderFactory->class(Utils::basename($className))->makeFinal()->addStmt(
            $this->builderFactory->method('__construct')->makePublic()->addParam(
                $this->builderFactory->param('operators')->makePublic()->setType('\\' . $package->namespace->source . '\\Internal\\Operators'),
            ),
        );

        foreach ($operations as $operation) {
            if ($operation->group !== $group) {
                continue;
            }

            $class->addStmt(
                Helper\Operation::methodSignature(
                    $this->builderFactory->method((new Convert($operation->name))->toCamel())->makePublic(),
                    $operation,
                )->addStmt(Helper\Operation::methodCallOperation($operation)),
            );
        }

        yield new File($package->destination->source, $className, $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }
}
