<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Paths;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\File;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use React\Http\Browser;
use ReflectionClass;

use function trim;
use function ucfirst;

final class Operators
{
    public function __construct(
        private BuilderFactory $builderFactory,
    ) {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Namespaced\Representation $representation): iterable
    {
        $operationHydratorMap = [];
        foreach ($representation->client->paths as $path) {
            foreach ($path->operations as $operation) {
                $operationHydratorMap[$operation->operationId] = $path->hydrator;
            }
        }

        $stmt = $this->builderFactory->namespace(trim($package->namespace->source, '\\') . '\\Internal');

        $class = $this->builderFactory->class('Operators')->makeFinal()->addStmt(
            $this->builderFactory->method('__construct')->makePublic()->addParam(
                $this->builderFactory->param('authentication')->makePrivate()->setType('\\' . AuthenticationInterface::class)->makeReadonly(),
            )->addParam(
                $this->builderFactory->param('browser')->makePrivate()->setType('\\' . Browser::class)->makeReadonly(),
            )->addParam(
                $this->builderFactory->param('requestSchemaValidator')->makePrivate()->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly(),
            )->addParam(
                $this->builderFactory->param('responseSchemaValidator')->makePrivate()->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly(),
            )->addParam(
                $this->builderFactory->param('hydrators')->makePrivate()->setType('Hydrators')->makeReadonly(),
            ),
        );

        foreach ($representation->client->paths as $path) {
            foreach ($path->operations as $operation) {
                $class->addStmts([
                    $this->builderFactory->property($operation->operatorLookUpMethod)->setType('?' . $operation->operatorClassName->fullyQualified->source)->setDefault(null)->makePrivate(),
                    $this->builderFactory->method($operation->operatorLookUpMethod)->setReturnType($operation->operatorClassName->fullyQualified->source)->makePublic()->addStmts([
                        new Node\Stmt\If_(
                            new Node\Expr\BinaryOp\Identical(
                                new Node\Expr\Instanceof_(
                                    new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        $operation->operatorLookUpMethod,
                                    ),
                                    new Node\Name($operation->operatorClassName->fullyQualified->source),
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
                                                new Node\Name($operation->operatorClassName->fullyQualified->source),
                                                [
                                                    ...(static function (Namespaced\Operation $operation, array $operationHydratorMap): iterable {
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
        }

        yield new File($package->destination->source, 'Internal\\Operators', $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }
}
