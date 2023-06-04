<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\ResultConverter;
use ApiClients\Tools\OpenApiClientGenerator\PrivatePromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
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
    /** @return iterable<File> */
    public static function generate(string $pathPrefix, Operation $operation, Representation\Hydrator $hydrator, ThrowableSchema $throwableSchemaRegistry, Configuration $configuration): iterable
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
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'METHOD',
                        new Node\Scalar\String_(
                            $operation->method,
                        ),
                    ),
                ],
                Class_::MODIFIER_PRIVATE,
            ),
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'PATH',
                        new Node\Scalar\String_(
                            $operation->path, // Deal with the query
                        ),
                    ),
                ],
                Class_::MODIFIER_PRIVATE,
            ),
        );

        $constructor = $factory->method('__construct')->makePublic();
        $constructor->addParam(
            (new PrivatePromotedPropertyAsParam('browser'))->setType('\\' . Browser::class),
        );
        $constructor->addParam(
            (new PrivatePromotedPropertyAsParam('authentication'))->setType('\\' . AuthenticationInterface::class),
        );

        if (count($operation->requestBody) > 0) {
            $constructor->addParam(
                (new PrivatePromotedPropertyAsParam('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            );
        }

        if ($bringHydratorAndResponseValidator) {
            $constructor->addParam(
                (new PrivatePromotedPropertyAsParam('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('hydrator'))->setType($hydrator->className->relative),
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

        $returnType = \ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation::getResultTypeFromOperation($operation);

        $class->addStmt(
            $factory->method('call')->makePublic()->setReturnType(
                $returnType,
            )->setDocComment(
                \ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation::getDocBlockFromOperation($operation),
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

        yield new File($pathPrefix, $operation->operatorClassName->relative, $stmt->addStmt($class)->getNode());
    }

    private static function callOperation(string $returnType, Operation $operation): Node\Expr\MethodCall
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
                    'returnType' => (static function (Representation\Operation $operation): Node\UnionType|Node\Name {
                        /** @phpstan-ignore-next-line */
                        $returnType = (new ReflectionClass($operation->className->fullyQualified->source))->getMethod('createResponse')->getReturnType();
                        if ($returnType === null) {
                            return new Node\Name('void');
                        }

                        if ((string) $returnType === 'void') {
                            return new Node\Name(
                                (string) $returnType,
                            );
                        }

                        return new Node\UnionType(
                            array_map(
                                static fn (string $object): Node\Name => new Node\Name((strpos($object, '\\') > 0 ? '\\' : '') . $object),
                                explode('|', (string) $returnType),
                            ),
                        );
                    })($operation),
                ])),
            ],
        );
    }
}
