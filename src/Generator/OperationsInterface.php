<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use Jawira\CaseConverter\Convert;
use PhpParser\BuilderFactory;

use function strlen;

final class OperationsInterface
{
    /**
     * @param array<Operation> $operations
     *
     * @return iterable<File>
     */
    public static function generate(Configuration $configuration, string $pathPrefix, array $operations): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($configuration->namespace->source);
        $class   = $factory->interface('OperationsInterface');

        /** @var array<string, array<Operation>> $groups */
        $groups = [];
        foreach ($operations as $operation) {
            $groups[$operation->group][] = $operation;
        }

        foreach ($groups as $group => $groupOperations) {
            if (strlen($group) > 0) {
                $class->addStmt(
                    $factory->method((new Convert($group))->toCamel())->makePublic()->setReturnType('Operation\\' . $group),
                );
                continue;
            }

            foreach ($groupOperations as $groupOperation) {
                $class->addStmt(
                    Helper\Operation::methodSignature(
                        $factory->method($groupOperation->nameCamel)->makePublic(),
                        $groupOperation,
                    ),
                );
            }
        }

        yield new File($pathPrefix, 'OperationsInterface', $stmt->addStmt($class)->getNode());
    }
}
