<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\ContentType\Json;
use ApiClients\Tools\OpenApiClientGenerator\ContentType\Raw;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Paths\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Paths\Operations;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Paths\OperationsInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Paths\OperationTest;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Paths\Operator;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Paths\Operators;
use OpenAPITools\Contract\FileGenerator;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced\Representation;
use OpenAPITools\Utils\File;
use PhpParser\BuilderFactory;

final readonly class Paths implements FileGenerator
{
    private Operators $operators;
    private Operator $operator;
    private OperationsInterface $operationsInterface;
    private Operations $operations;
    private Operation $operation;
    private OperationTest $operationTest;

    public function __construct(
        BuilderFactory $builderFactory,
        bool $call,
        bool $operations,
    ) {
        $this->operators           = new Operators($builderFactory);
        $this->operator            = new Operator($builderFactory);
        $this->operationsInterface = new OperationsInterface($builderFactory);
        $this->operations          = new Operations($builderFactory);
        $this->operation           = new Operation($builderFactory, new Json(), new Raw());
        $this->operationTest       = new OperationTest($builderFactory, $call, $operations);
    }

    /** @return iterable<File> */
    public function generate(Package $package, Representation $representation): iterable
    {
        foreach ($representation->client->paths as $path) {
            foreach ($path->operations as $operation) {
                yield from $this->operation->generate($package, $operation, $path->hydrator);
                yield from $this->operator->generate($package, $operation, $path->hydrator);
                yield from $this->operationTest->generate($package, $operation);
            }
        }

        yield from $this->operationsInterface->generate($package, $representation);
        yield from $this->operations->generate($package, $representation);
        yield from $this->operators->generate($package, $representation);
    }
}
