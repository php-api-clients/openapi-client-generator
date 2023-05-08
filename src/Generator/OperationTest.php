<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use Jawira\CaseConverter\Convert;
use PhpParser\Builder\Method;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use Prophecy\Argument;
use React\Http\Browser;
use React\Http\Message\Response;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function count;
use function implode;
use function Safe\preg_replace;
use function str_replace;

use const PHP_EOL;

final class OperationTest
{
    /**
     * @return iterable<File>
     */
    public static function generate(string $pathPrefix, Operation $operation, Hydrator $hydrator, ThrowableSchema $throwableSchemaRegistry, Configuration $configuration): iterable
    {
        if (count($operation->response) === 0) {
            return;
        }

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($operation->className->namespace->test);

        $class = $factory->class($operation->className->className . 'Test')->extend(
            new Node\Name(
                '\\' . AsyncTestCase::class,
            )
        )->makeFinal();

        foreach ($operation->response as $contentTypeSchema) {
            if (count($operation->requestBody) === 0) {
                if ($configuration->entryPoints->call) {
                    $class->addStmt(
                        self::createCallMethod(
                            $factory,
                            $operation,
                            null,
                            $contentTypeSchema,
                            $configuration,
                        ),
                    );
                }

                if ($configuration->entryPoints->operations) {
                    $class->addStmt(
                        self::createOperationsMethod(
                            $factory,
                            $operation,
                            null,
                            $contentTypeSchema,
                            $configuration,
                        ),
                    );
                }
            } else {
                foreach ($operation->requestBody as $request) {
                    if ($configuration->entryPoints->call) {
                        $class->addStmt(
                            self::createCallMethod(
                                $factory,
                                $operation,
                                $request,
                                $contentTypeSchema,
                                $configuration,
                            ),
                        );
                    }

                    if (! $configuration->entryPoints->operations) {
                        continue;
                    }

                    $class->addStmt(
                        self::createOperationsMethod(
                            $factory,
                            $operation,
                            $request,
                            $contentTypeSchema,
                            $configuration,
                        ),
                    );
                }
            }
        }

        yield new File($pathPrefix, $operation->className->relative . 'Test', $stmt->addStmt($class)->getNode());
    }

    private static function createCallMethod(BuilderFactory $factory, Operation $operation, OperationRequestBody|null $request, OperationResponse $response, Configuration $configuration): Method
    {
        $responseSchemaFetch = new Node\Expr\ClassConstFetch(
            new Node\Name(
                $response->schema->className->relative,
            ),
            'SCHEMA_EXAMPLE_DATA',
        );

        return $factory->method('call_httpCode_' . $response->code . ($request === null ? '' : '_requestContentType_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $request->contentType)) . '_responseContentType_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $response->contentType))->makePublic()->setDocComment(
            new Doc(implode(PHP_EOL, [
                '/**',
                ' * @test',
                ' */',
            ]))
        )->addStmts([
            ...self::testSetUp($responseSchemaFetch, $operation, $request, $response, $configuration),
            new Node\Expr\MethodCall(
                new Node\Expr\Variable(
                    'client',
                ),
                'call',
                [
                    new Arg(
                        new Node\Expr\ClassConstFetch(
                            new Node\Name(
                                $operation->className->relative,
                            ),
                            'OPERATION_MATCH',
                        )
                    ),
                    new Arg(
                        new Node\Expr\FuncCall(
                            new Node\Expr\Closure(
                                [
                                    'static' => true,
                                    'returnType' => new Node\Name(
                                        'array',
                                    ),
                                    'params' => [
                                        (new Param(
                                            'data',
                                        ))->setType(
                                            new Node\Name(
                                                'array',
                                            ),
                                        )->getNode(),
                                    ],
                                    'stmts' => [
                                        ...((static function (array $parameters): iterable {
                                            foreach ($parameters as $parameter) {
                                                yield new Node\Stmt\Expression(
                                                    new Node\Expr\Assign(
                                                        new Node\Expr\ArrayDimFetch(
                                                            new Node\Expr\Variable(
                                                                'data',
                                                            ),
                                                            new Node\Scalar\String_($parameter->targetName),
                                                        ),
                                                        $parameter->exampleNode
                                                    ),
                                                );
                                            }
                                        })($operation->parameters)),
                                        new Node\Stmt\Return_(
                                            new Node\Expr\Variable(
                                                'data',
                                            ),
                                        ),
                                    ],
                                ],
                            ),
                            [
                                new Arg(
                                    ($request === null ? new Node\Expr\Array_() : new Node\Expr\FuncCall(
                                        new Node\Name(
                                            'json_decode',
                                        ),
                                        [
                                            new Arg(
                                                new Node\Expr\ClassConstFetch(
                                                    new Node\Name(
                                                        $request->schema->className->relative,
                                                    ),
                                                    'SCHEMA_EXAMPLE_DATA',
                                                ),
                                            ),
                                            new Arg(
                                                new Node\Expr\ConstFetch(
                                                    new Node\Name(
                                                        'true',
                                                    ),
                                                ),
                                            ),
                                        ],
                                    )),
                                ),
                            ],
                        ),
                    ),
                ],
            ),
        ]);
    }

    private static function createOperationsMethod(BuilderFactory $factory, Operation $operation, OperationRequestBody|null $request, OperationResponse $response, Configuration $configuration): Method
    {
        $responseSchemaFetch = new Node\Expr\ClassConstFetch(
            new Node\Name(
                $response->schema->className->relative,
            ),
            'SCHEMA_EXAMPLE_DATA',
        );

        return $factory->method('operations_httpCode_' . $response->code . ($request === null ? '' : '_requestContentType_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $request->contentType)) . '_responseContentType_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $response->contentType))->makePublic()->setDocComment(
            new Doc(implode(PHP_EOL, [
                '/**',
                ' * @test',
                ' */',
            ]))
        )->addStmts([
            ...self::testSetUp($responseSchemaFetch, $operation, $request, $response, $configuration),
            new Node\Expr\FuncCall(
                new Node\Name(
                    '\React\Async\await',
                ),
                [
                    new Arg(
                        new Node\Expr\MethodCall(
                            new Node\Expr\MethodCall(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable(
                                        'client',
                                    ),
                                    'operations',
                                ),
                                (new Convert($operation->group))->toCamel(),
                            ),
                            (new Convert($operation->name))->toCamel(),
                            [
                                ...((static function (array $parameters): iterable {
                                    foreach ($parameters as $parameter) {
                                        yield new Arg($parameter->exampleNode);
                                    }
                                })($operation->parameters)),
                                ...($request === null ? [] : [
                                    new Arg(new Node\Expr\FuncCall(
                                        new Node\Name(
                                            'json_decode',
                                        ),
                                        [
                                            new Arg(
                                                new Node\Expr\ClassConstFetch(
                                                    new Node\Name(
                                                        $request->schema->className->relative,
                                                    ),
                                                    'SCHEMA_EXAMPLE_DATA',
                                                ),
                                            ),
                                            new Arg(
                                                new Node\Expr\ConstFetch(
                                                    new Node\Name(
                                                        'true',
                                                    ),
                                                ),
                                            ),
                                        ],
                                    )),
                                ]),
                            ],
                        ),
                    ),
                ],
            ),
        ]);
    }

    private static function wrapShouldBeCalled(Node\Expr\MethodCall $methodCall): Node\Expr\MethodCall
    {
        return new Node\Expr\MethodCall(
            $methodCall,
            'shouldBeCalled',
        );
    }

    /**
     * @return array<Node>
     */
    private static function testSetUp(Node\Expr\ClassConstFetch $responseSchemaFetch, Operation $operation, OperationRequestBody|null $request, OperationResponse $response, Configuration $configuration): array
    {
        return [
            ...($response->code < 400 ? [] : [
                new Node\Stmt\Expression(
                    new Node\Expr\StaticCall(
                        new Node\Expr\ConstFetch(
                            new Node\Name(
                                'self',
                            ),
                        ),
                        'expectException',
                        [
                            new Arg(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name(
                                        $response->schema->errorClassNameAliased->relative,
                                    ),
                                    'class',
                                ),
                            ),
                        ],
                    ),
                ),
            ]),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable(
                        'response',
                    ),
                    new Node\Expr\New_(
                        new Node\Name(
                            '\\' . Response::class,
                        ),
                        [
                            new Arg(
                                new Node\Scalar\LNumber(
                                    $response->code,
                                ),
                            ),
                            new Arg(
                                new Node\Expr\Array_([
                                    new Node\Expr\ArrayItem(
                                        new Node\Scalar\String_($response->contentType),
                                        new Node\Scalar\String_('Content-Type'),
                                    ),
                                ]),
                            ),
                            new Arg(
                                $response->schema->isArray ? new Node\Expr\BinaryOp\Concat(
                                    new Node\Scalar\String_('['),
                                    new Node\Expr\BinaryOp\Concat(
                                        $responseSchemaFetch,
                                        new Node\Scalar\String_(']'),
                                    ),
                                ) : $responseSchemaFetch,
                            ),
                        ],
                    ),
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable(
                        'auth',
                    ),
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable(
                            'this',
                        ),
                        'prophesize',
                        [
                            new Arg(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name(
                                        '\\' . AuthenticationInterface::class,
                                    ),
                                    'class',
                                )
                            ),
                        ],
                    ),
                ),
            ),
            self::wrapShouldBeCalled(
                new Node\Expr\MethodCall(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable(
                            'auth',
                        ),
                        'authHeader',
                        [
                            new Arg(
                                new Node\Expr\StaticCall(
                                    new Node\Name(
                                        '\\' . Argument::class,
                                    ),
                                    'any',
                                ),
                            ),
                        ],
                    ),
                    'willReturn',
                    [
                        new Arg(
                            new Node\Scalar\String_('Bearer beer'),
                        ),
                    ],
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable(
                        'browser',
                    ),
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable(
                            'this',
                        ),
                        'prophesize',
                        [
                            new Arg(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name(
                                        '\\' . Browser::class,
                                    ),
                                    'class',
                                )
                            ),
                        ],
                    ),
                ),
            ),
            new Node\Expr\MethodCall(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable(
                        'browser',
                    ),
                    'withBase',
                    [
                        new Arg(
                            new Node\Expr\StaticCall(
                                new Node\Name(
                                    '\\' . Argument::class,
                                ),
                                'any',
                            ),
                        ),
                    ],
                ),
                'willReturn',
                [
                    new Arg(
                        new Node\Expr\MethodCall(
                            new Node\Expr\Variable(
                                'browser',
                            ),
                            'reveal',
                        ),
                    ),
                ],
            ),
            new Node\Expr\MethodCall(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable(
                        'browser'
                    ),
                    'withFollowRedirects',
                    [
                        new Arg(
                            new Node\Expr\StaticCall(
                                new Node\Name(
                                    '\\' . Argument::class,
                                ),
                                'any',
                            ),
                        ),
                    ],
                ),
                'willReturn',
                [
                    new Arg(
                        new Node\Expr\MethodCall(
                            new Node\Expr\Variable(
                                'browser',
                            ),
                            'reveal',
                        ),
                    ),
                ],
            ),
            self::wrapShouldBeCalled(
                new Node\Expr\MethodCall(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable(
                            'browser',
                        ),
                        'request',
                        [
                            new Arg(
                                new Node\Scalar\String_(
                                    $operation->method,
                                )
                            ),
                            new Arg(
                                new Node\Scalar\String_(
                                    str_replace(
                                        (static function (array $parameters): array {
                                            $items = [];
                                            foreach ($parameters as $parameter) {
                                                if ($parameter->location !== 'path') {
                                                    continue;
                                                }

                                                $items[] = '{' . $parameter->targetName . '}';
                                            }

                                            return $items;
                                        })($operation->parameters),
                                        (static function (array $parameters): array {
                                            $items = [];
                                            foreach ($parameters as $parameter) {
                                                if ($parameter->location !== 'path') {
                                                    continue;
                                                }

                                                $items[] = $parameter->example;
                                            }

                                            return $items;
                                        })($operation->parameters),
                                        $operation->path,
                                    ) . (static function (array $parameters): string {
                                        $items = [];
                                        foreach ($parameters as $parameter) {
                                            if ($parameter->location !== 'query') {
                                                continue;
                                            }

                                            $items[] = $parameter->targetName . '=' . $parameter->example;
                                        }

                                        return count($items) > 0 ? '?' . implode('&', $items) : '';
                                    })($operation->parameters),
                                )
                            ),
                            new Arg(
                                new Node\Expr\StaticCall(
                                    new Node\Name(
                                        '\\' . Argument::class,
                                    ),
                                    'type',
                                    [
                                        new Arg(
                                            new Node\Scalar\String_(
                                                'array',
                                            ),
                                        ),
                                    ],
                                ),
                            ),
                            new Arg(
                                $request === null ? new Node\Expr\StaticCall(
                                    new Node\Name(
                                        '\\' . Argument::class,
                                    ),
                                    'any',
                                ) : new Node\Expr\ClassConstFetch(
                                    new Node\Name(
                                        $request->schema->className->relative,
                                    ),
                                    'SCHEMA_EXAMPLE_DATA',
                                ),
                            ),
                        ],
                    ),
                    'willReturn',
                    [
                        new Arg(
                            new Node\Expr\FuncCall(
                                new Node\Name(
                                    '\React\Promise\resolve',
                                ),
                                [
                                    new Arg(
                                        new Node\Expr\Variable(
                                            'response',
                                        ),
                                    ),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable(
                        'client',
                    ),
                    new Node\Expr\New_(
                        new Node\Name(
                            '\\' . $configuration->namespace->source . '\Client',
                        ),
                        [
                            new Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable(
                                        'auth',
                                    ),
                                    'reveal',
                                ),
                            ),
                            new Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable(
                                        'browser',
                                    ),
                                    'reveal',
                                ),
                            ),
                        ],
                    ),
                ),
            ),
        ];
    }
}
