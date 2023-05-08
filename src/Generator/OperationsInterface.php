<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use Jawira\CaseConverter\Convert;
use PhpParser\BuilderFactory;

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

        $groups = [];
        foreach ($operations as $operation) {
            $groups[$operation->group] = $operation->group;
        }

        foreach ($groups as $group) {
            $class->addStmt(
                $factory->method((new Convert($group))->toCamel())->makePublic()->setReturnType('Operation\\' . $group),
            );
        }

        yield new File($pathPrefix, 'OperationsInterface', $stmt->addStmt($class)->getNode());
    }
}
