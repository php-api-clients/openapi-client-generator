<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Gatherer\ExampleData;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use Jawira\CaseConverter\Convert;
use NumberToWords\NumberToWords;
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
use function is_array;
use function Safe\preg_replace;
use function str_replace;
use function strtolower;

use const PHP_EOL;

final class OperationTest
{
    /**
     * @return iterable<File>
     */
    public static function generate(string $pathPrefix, Representation\Operation $operation, Representation\Hydrator $hydrator, ThrowableSchema $throwableSchemaRegistry, Configuration $configuration): iterable
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
            $contentTypePayloads = $contentTypeSchema->content->payload;
            if (! is_array($contentTypePayloads)) {
                $contentTypePayloads = [$contentTypePayloads];
            }

            foreach ($contentTypePayloads as $index => $contentTypePayload) {
                if (! $contentTypePayload instanceof Representation\Schema) {
                    continue;
                }

                $testSuffix = NumberToWords::transformNumber('en', $index);
                if (count($operation->requestBody) === 0) {
                    if ($configuration->entryPoints->call) {
                        $class->addStmt(
                            self::createCallMethod(
                                $factory,
                                $operation,
                                null,
                                $contentTypeSchema,
                                $configuration,
                                $contentTypePayload,
                                $testSuffix,
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
                                $contentTypePayload,
                                $testSuffix,
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
                                    $contentTypePayload,
                                    $testSuffix,
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
                                $contentTypePayload,
                                $testSuffix,
                            ),
                        );
                    }
                }
            }
        }

        foreach ($operation->empty as $emptyResponse) {
            if (count($operation->requestBody) === 0) {
                if ($configuration->entryPoints->call) {
                    $class->addStmt(
                        self::createCallMethod(
                            $factory,
                            $operation,
                            null,
                            $emptyResponse,
                            $configuration,
                            null,
                            'empty',
                        ),
                    );
                }

                if ($configuration->entryPoints->operations) {
                    $class->addStmt(
                        self::createOperationsMethod(
                            $factory,
                            $operation,
                            null,
                            $emptyResponse,
                            $configuration,
                            null,
                            'empty',
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
                                $emptyResponse,
                                $configuration,
                                null,
                                'empty',
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
                            $emptyResponse,
                            $configuration,
                            null,
                            'empty',
                        ),
                    );
                }
            }
        }

        yield new File($pathPrefix, $operation->className->relative . 'Test', $stmt->addStmt($class)->getNode());
    }

    private static function createCallMethod(BuilderFactory $factory, Representation\Operation $operation, Representation\OperationRequestBody|null $request, Representation\OperationResponse|Representation\OperationEmptyResponse $response, Configuration $configuration, Representation\Schema|Representation\PropertyType|string|null $contentTypePayload, string $testSuffix): Method
    {
        $methodName = 'call_httpCode_' . $response->code . ($request === null ? '' : '_requestContentType_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $request->contentType)) . ($response instanceof Representation\OperationResponse ? '_responseContentType_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $response->contentType) : '') . ($testSuffix !== '' ? '_' . $testSuffix : '');
        if ($response instanceof Representation\OperationResponse && $response->content->payload instanceof Representation\Schema) {
            $responseSchemaFetch = new Node\Expr\ClassConstFetch(
                new Node\Name(
                    $response->content->payload->className->relative,
                ),
                'SCHEMA_EXAMPLE_DATA',
            );
        } elseif ($response instanceof Representation\OperationResponse) {
            $responseSchemaFetch = ExampleData::gather(null, $response->content, $methodName)->node;
        } else {
            $responseSchemaFetch = new Node\Scalar\String_('');
        }

        return $factory->method($methodName)->makePublic()->setDocComment(
            new Doc(implode(PHP_EOL, [
                '/**',
                ' * @test',
                ' */',
            ]))
        )->addStmts([
            ...self::testSetUp($responseSchemaFetch, $operation, $request, $response, $configuration, $contentTypePayload),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable(
                        'result',
                    ),
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
                                                                $parameter->example->node
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
                ),
            ),
        ]);
    }

    private static function createOperationsMethod(BuilderFactory $factory, Representation\Operation $operation, Representation\OperationRequestBody|null $request, Representation\OperationResponse|Representation\OperationEmptyResponse $response, Configuration $configuration, Representation\Schema|Representation\PropertyType|string|null $contentTypePayload, string $testSuffix): Method
    {
        $methodName = 'operations_httpCode_' . $response->code . ($request === null ? '' : '_requestContentType_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $request->contentType)) . ($response instanceof Representation\OperationResponse ? '_responseContentType_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $response->contentType) : '') . ($testSuffix !== '' ? '_' . $testSuffix : '');
        if ($response instanceof Representation\OperationResponse && $response->content->payload instanceof Representation\Schema) {
            $responseSchemaFetch = new Node\Expr\ClassConstFetch(
                new Node\Name(
                    $response->content->payload->className->relative,
                ),
                'SCHEMA_EXAMPLE_DATA',
            );
        } elseif ($response instanceof Representation\OperationResponse) {
            $responseSchemaFetch = ExampleData::gather(null, $response->content, $methodName)->node;
        } else {
            $responseSchemaFetch = new Node\Scalar\String_('');
        }

        return $factory->method($methodName)->makePublic()->setDocComment(
            new Doc(implode(PHP_EOL, [
                '/**',
                ' * @test',
                ' */',
            ]))
        )->addStmts([
            ...self::testSetUp($responseSchemaFetch, $operation, $request, $response, $configuration, $contentTypePayload),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable(
                        'result',
                    ),
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
                                                yield new Arg($parameter->example->node);
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
                ),
            ),
            ...($operation->matchMethod !== 'STREAM' && $response instanceof Representation\OperationEmptyResponse ? [
                new Node\Expr\StaticCall(
                    new Node\Name('self'),
                    'assertArrayHasKey',
                    [
                        new Arg(
                            new Node\Scalar\String_('code'),
                        ),
                        new Arg(
                            new Node\Expr\Variable(
                                'result',
                            ),
                        ),
                    ],
                ),
                new Node\Expr\StaticCall(
                    new Node\Name('self'),
                    'assertSame',
                    [
                        new Arg(
                            new Node\Scalar\LNumber(
                                $response->code,
                            ),
                        ),
                        new Arg(
                            new Node\Expr\ArrayDimFetch(
                                new Node\Expr\Variable(
                                    'result',
                                ),
                                new Node\Scalar\String_('code'),
                            ),
                        ),
                    ],
                ),
                ...(static function (Representation\Header ...$headers): iterable {
                    foreach ($headers as $header) {
                        yield new Node\Expr\StaticCall(
                            new Node\Name('self'),
                            'assertArrayHasKey',
                            [
                                new Arg(
                                    new Node\Scalar\String_(strtolower($header->name)),
                                ),
                                new Arg(
                                    new Node\Expr\Variable(
                                        'result',
                                    ),
                                ),
                            ],
                        );
                        yield new Node\Expr\StaticCall(
                            new Node\Name('self'),
                            'assertSame',
                            [
                                new Arg(
                                    $header->example->node,
                                ),
                                new Arg(
                                    new Node\Expr\ArrayDimFetch(
                                        new Node\Expr\Variable(
                                            'result',
                                        ),
                                        new Node\Scalar\String_(strtolower($header->name)),
                                    ),
                                ),
                            ],
                        );
                    }
                })(...$response->headers),
            ] : []),
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
    private static function testSetUp(Node\Expr $responseSchemaFetch, Representation\Operation $operation, Representation\OperationRequestBody|null $request, Representation\OperationResponse|Representation\OperationEmptyResponse $response, Configuration $configuration, Representation\Schema|Representation\PropertyType|string|null $contentTypePayload): array
    {
        return [
            ...($response->code < 400 || $contentTypePayload === null ? [] : [
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
                                        $contentTypePayload->errorClassNameAliased->relative,
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
                            ...($response instanceof Representation\OperationResponse ? [
                                new Arg(
                                    new Node\Expr\Array_([
                                        new Node\Expr\ArrayItem(
                                            new Node\Scalar\String_($response->contentType),
                                            new Node\Scalar\String_('Content-Type'),
                                        ),
                                    ]),
                                ),
                                new Arg(
                                    $contentTypePayload instanceof Schema && $contentTypePayload->isArray ? new Node\Expr\BinaryOp\Concat(
                                        new Node\Scalar\String_('['),
                                        new Node\Expr\BinaryOp\Concat(
                                            $responseSchemaFetch,
                                            new Node\Scalar\String_(']'),
                                        ),
                                    ) : $responseSchemaFetch,
                                ),
                            ] : [
                                new Arg(
                                    new Node\Expr\Array_([
                                        ...(static function (Representation\Header ...$headers): iterable {
                                            foreach ($headers as $header) {
                                                yield new Node\Expr\ArrayItem(
                                                    $header->example->node,
                                                    new Node\Scalar\String_($header->name),
                                                );
                                            }
                                        })(...$response->headers),
                                    ]),
                                ),
                            ]),
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

                                                $items[] = $parameter->example->raw;
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

                                            $items[] = $parameter->targetName . '=' . $parameter->example->raw;
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
