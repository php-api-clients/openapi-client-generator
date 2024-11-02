<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Paths;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\ResultConverter;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Types;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\File;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use ReflectionClass;
use ReflectionParameter;

use function array_filter;
use function array_map;
use function count;
use function explode;
use function strpos;

final class Operator
{
    public function __construct(
        private BuilderFactory $builderFactory,
    ) {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Namespaced\Operation $operation, Namespaced\Hydrator $hydrator): iterable
    {
        $bringHydratorAndResponseValidator = count(
            array_filter(
                /** @phpstan-ignore-next-line */
                (new ReflectionClass($operation->className->fullyQualified->source))->getConstructor()->getParameters(),
                static fn (ReflectionParameter $parameter): bool => $parameter->name === 'responseSchemaValidator' || $parameter->name === 'hydrator',
            ),
        ) > 0;
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($operation->operatorClassName->namespace->source);

        $class = $factory->class($operation->operatorClassName->className)->makeFinal()->makeReadonly()->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'OPERATION_ID',
                        new Node\Scalar\String_(
                            $operation->operationId,
                        ),
                    ),
                ],
                Class_::MODIFIER_PUBLIC,
            ),
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'OPERATION_MATCH',
                        new Node\Scalar\String_(
                            $operation->matchMethod . ' ' . $operation->path, // Deal with the query
                        ),
                    ),
                ],
                Class_::MODIFIER_PUBLIC,
            ),
        );

        $constructor = $factory->method('__construct')->makePublic();
        $constructor->addParam(
            $this->builderFactory->param('browser')->makePrivate()->setType('\\' . Browser::class),
        );
        $constructor->addParam(
            $this->builderFactory->param('authentication')->makePrivate()->setType('\\' . AuthenticationInterface::class),
        );

        if (count($operation->requestBody) > 0) {
            $constructor->addParam(
                $this->builderFactory->param('requestSchemaValidator')->makePrivate()->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            );
        }

        if ($bringHydratorAndResponseValidator) {
            $constructor->addParam(
                $this->builderFactory->param('responseSchemaValidator')->makePrivate()->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addParam(
                $this->builderFactory->param('hydrator')->makePrivate()->setType($hydrator->className->fullyQualified->source),
            );
        }

        $class->addStmt($constructor);

        $callParams = [];
        foreach ($operation->parameters as $parameter) {
            $param = new Param($parameter->name);

            if ($parameter->type !== '') {
                $param->setType($parameter->type);
            }

            if ($parameter->default !== null) {
                $param->setDefault($parameter->default);
            }

            $callParams[] = $param;
        }

        if (count($operation->requestBody) > 0) {
            $callParams[] = $factory->param('params')->setType('array');
        }

        $returnType = Operation::getResultTypeFromOperation($operation);

        $class->addStmt(
            $factory->method('call')->makePublic()->setReturnType(
                new Node\UnionType(
                    array_map(
                        static fn (string $object): Node\Name => new Node\Name((strpos($object, '\\') > 0 ? '\\' : '') . $object),
                        [...Types::filterDuplicatesAndIncompatibleRawTypes(...explode('|', (string) $returnType))],
                    ),
                ),
            )->setDocComment(
                Operation::getDocBlockFromOperation($operation),
            )->addParams($callParams)->addStmts([
                new Node\Stmt\Expression(new Node\Expr\Assign(
                    new Node\Expr\Variable('operation'),
                    new Node\Expr\New_(
                        new Node\Name($operation->className->fullyQualified->source),
                        [
                            ...(count($operation->requestBody) > 0 ? [
                                new Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'requestSchemaValidator',
                                )),
                            ] : []),
                            ...($bringHydratorAndResponseValidator ? [
                                new Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'responseSchemaValidator',
                                )),
                                new Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'hydrator',
                                )),
                            ] : []),
                            ...($operation->matchMethod === 'STREAM' ? [
                                new Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'browser',
                                )),
                            ] : []),
                            ...(static function (array $params): iterable {
                                foreach ($params as $param) {
                                    yield new Arg(new Node\Expr\Variable($param->name));
                                }
                            })($operation->parameters),
                        ],
                    ),
                )),
                new Node\Stmt\Expression(new Node\Expr\Assign(new Node\Expr\Variable('request'), new Node\Expr\MethodCall(new Node\Expr\Variable('operation'), 'createRequest', count($operation->requestBody) > 0 ? [new Arg(new Node\Expr\Variable('params'))] : []))),
                ...($returnType === 'void' ? [self::callOperation($returnType, $operation)] : ResultConverter::convert(
                    self::callOperation($returnType, $operation),
                )),
            ]),
        );

        yield new File($package->destination->source, $operation->operatorClassName->relative, $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }

    private static function callOperation(string $returnType, Namespaced\Operation $operation): Node\Expr\MethodCall
    {
        return new Node\Expr\MethodCall(
            new Node\Expr\MethodCall(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'browser',
                ),
                'request',
                [
                    new Node\Arg(new Node\Expr\MethodCall(new Node\Expr\Variable('request'), 'getMethod')),
                    new Node\Arg(new Expr\Cast\String_(new Node\Expr\MethodCall(new Node\Expr\Variable('request'), 'getUri'))),
                    new Node\Arg(
                        new Node\Expr\MethodCall(
                            new Node\Expr\MethodCall(
                                new Node\Expr\Variable('request'),
                                'withHeader',
                                [
                                    new Node\Arg(new Node\Scalar\String_('Authorization')),
                                    new Node\Arg(
                                        new Node\Expr\MethodCall(
                                            new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                'authentication',
                                            ),
                                            'authHeader',
                                        ),
                                    ),
                                ],
                            ),
                            'getHeaders',
                        ),
                    ),
                    new Node\Arg(new Expr\Cast\String_(new Node\Expr\MethodCall(new Node\Expr\Variable('request'), 'getBody'))),
                ],
            ),
            'then',
            [
                new Arg(new Node\Expr\Closure([
                    'stmts' => [
                        $returnType === 'void' ? new Node\Stmt\Expression(new Node\Expr\MethodCall(new Node\Expr\Variable('operation'), 'createResponse', [
                            new Arg(new Node\Expr\Variable('response')),
                        ])) : new Node\Stmt\Return_(new Node\Expr\MethodCall(new Node\Expr\Variable('operation'), 'createResponse', [
                            new Arg(new Node\Expr\Variable('response')),
                        ])),
                    ],
                    'params' => [new Node\Param(new Node\Expr\Variable('response'), null, new Node\Name('\\' . ResponseInterface::class))],
                    'uses' => [
                        new Node\Expr\Variable('operation'),
                    ],
                    'returnType' => (static function (Namespaced\Operation $operation): Node\UnionType|Node\Name {
                        /** @phpstan-ignore-next-line */
                        $returnType = (new ReflectionClass($operation->className->fullyQualified->source))->getMethod('createResponse')->getReturnType();
                        if ($returnType === null || (string) $returnType === 'void') {
                            return new Node\Name('void');
                        }

                        return new Node\UnionType(
                            array_map(
                                static fn (string $object): Node\Name => new Node\Name((strpos($object, '\\') > 0 ? '\\' : '') . $object),
                                [...Types::filterDuplicatesAndIncompatibleRawTypes(...explode('|', (string) $returnType))],
                            ),
                        );
                    })($operation),
                ])),
            ],
        );
    }
}
