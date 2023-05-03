<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use Jawira\CaseConverter\Convert;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use React\Promise\PromiseInterface;
use Rx\Observable;

use function array_map;
use function array_unique;
use function count;
use function implode;
use function strpos;
use function trim;

use const PHP_EOL;

final class OperationsInterface
{
    /**
     * @param array<Operation> $paths
     *
     * @return iterable
     */
    public static function generate(Configuration $configuration, string $pathPrefix, string $namespace, array $operations): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim($namespace, '\\'));

        $class = $factory->interface('OperationsInterface');

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
