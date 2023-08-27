<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\PrivatePromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use React\Http\Browser;
use ReflectionClass;

use function trim;
use function ucfirst;

final class Operators
{
    /**
     * @param array<Operation>        $operations
     * @param array<string, Hydrator> $operationHydratorMap
     *
     * @return iterable<File>
     */
    public static function generate(Configuration $configuration, string $pathPrefix, array $operations, array $operationHydratorMap): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim($configuration->namespace->source, '\\') . '\\Internal');

        $class = $factory->class('Operators')->makeFinal()->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new PrivatePromotedPropertyAsParam('authentication'))->setType('\\' . AuthenticationInterface::class)->makeReadonly(),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('browser'))->setType('\\' . Browser::class)->makeReadonly(),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly(),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly(),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('hydrators'))->setType('Internal\\Hydrators')->makeReadonly(),
            ),
        );

        foreach ($operations as $operation) {
            $class->addStmts([
                $factory->property($operation->operatorLookUpMethod)->setType('?' . $operation->operatorClassName->relative)->setDefault(null)->makePrivate(),
                $factory->method($operation->operatorLookUpMethod)->setReturnType($operation->operatorClassName->relative)->makePublic()->addStmts([
                    new Node\Stmt\If_(
                        new Node\Expr\BinaryOp\Identical(
                            new Node\Expr\Instanceof_(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    $operation->operatorLookUpMethod,
                                ),
                                new Node\Name($operation->operatorClassName->relative),
                            ),
                            new Node\Expr\ConstFetch(new Node\Name('false')),
                        ),
                        [
                            'stmts' => [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Assign(
                                        new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            $operation->operatorLookUpMethod,
                                        ),
                                        new Node\Expr\New_(
                                            new Node\Name($operation->operatorClassName->relative),
                                            [
                                                ...(static function (Operation $operation, array $operationHydratorMap): iterable {
                                                    foreach ((new ReflectionClass($operation->operatorClassName->fullyQualified->source))->getConstructor()->getParameters() as $parameter) {
                                                        if ($parameter->name === 'hydrator') {
                                                            yield new Arg(
                                                                new Node\Expr\MethodCall(
                                                                    new Node\Expr\PropertyFetch(
                                                                        new Node\Expr\Variable('this'),
                                                                        'hydrators',
                                                                    ),
                                                                    'getObjectMapper' . ucfirst($operationHydratorMap[$operation->operationId]->methodName),
                                                                ),
                                                            );
                                                            continue;
                                                        }

                                                        yield new Arg(
                                                            new Node\Expr\PropertyFetch(
                                                                new Node\Expr\Variable('this'),
                                                                $parameter->name,
                                                            ),
                                                        );
                                                    }
                                                })($operation, $operationHydratorMap),
                                            ],
                                        ),
                                    ),
                                ),
                            ],
                        ],
                    ),
                    new Node\Stmt\Return_(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            $operation->operatorLookUpMethod,
                        ),
                    ),
                ]),
            ]);
        }

        yield new File($pathPrefix, 'Internal\\Operators', $stmt->addStmt($class)->getNode());
    }
}
