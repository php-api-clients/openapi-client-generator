<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Paths;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;
use Jawira\CaseConverter\Convert;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Representation\Namespaced\Representation;
use OpenAPITools\Utils\File;
use PhpParser\BuilderFactory;

use function strlen;

final class OperationsInterface
{
    public function __construct(
        private BuilderFactory $builderFactory,
    ) {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Representation $representation): iterable
    {
        $stmt  = $this->builderFactory->namespace($package->namespace->source);
        $class = $this->builderFactory->interface('OperationsInterface');

        /** @var array<string, array<Namespaced\Operation>> $groups */
        $groups = [];
        foreach ($representation->client->paths as $path) {
            foreach ($path->operations as $operation) {
                $groups[$operation->group][] = $operation;
            }
        }

        foreach ($groups as $group => $groupOperations) {
            if (strlen($group) > 0) {
                $class->addStmt(
                    $this->builderFactory->method((new Convert($group))->toCamel())->makePublic()->setReturnType('Operation\\' . $group),
                );
                continue;
            }

            foreach ($groupOperations as $groupOperation) {
                $class->addStmt(
                    Helper\Operation::methodSignature(
                        $this->builderFactory->method($groupOperation->nameCamel)->makePublic(),
                        $groupOperation,
                    ),
                );
            }
        }

        yield new File($package->destination->source, 'OperationsInterface', $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }
}
