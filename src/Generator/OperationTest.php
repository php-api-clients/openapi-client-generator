<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Client\Github\Schema\InstallationToken;
use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRedirect;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Parameter;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use PhpParser\Builder\Method;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Stmt\Class_;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use React\Http\Browser;
use React\Http\Message\Response;
use RingCentral\Psr7\Request;
use Rx\Observable;
use Rx\Scheduler\ImmediateScheduler;
use Rx\Subject\Subject;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

final class OperationTest
{
    /**
     * @return iterable<Node>
     */
    public static function generate(string $pathPrefix, string $namespace, string $sourceNamespace, \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation $operation, \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator $hydrator, ThrowableSchema $throwableSchemaRegistry, Configuration $configuration): iterable
    {
        if (count($operation->response) === 0) {
            return;
        }

        $factory = new BuilderFactory();
        $stmt = $factory->namespace(ltrim(Utils::dirname($namespace . '\\Operation\\' . $operation->className), '\\'));

        $class = $factory->class(
            Utils::className(ltrim(Utils::basename($operation->className), '\\')) . 'Test',
        )->extend(
            new Node\Name(
                '\\' . AsyncTestCase::class,
            )
        )->makeFinal();

        foreach ($operation->response as $contentTypeSchema) {
            if (count($operation->requestBody) === 0) {
                $class->addStmt(
                    self::createMethod(
                        $factory,
                        $operation,
                        null,
                        $contentTypeSchema,
                        $sourceNamespace,
                    ),
                );
            } else {
                foreach ($operation->requestBody as $request) {
                    $class->addStmt(
                        self::createMethod(
                            $factory,
                            $operation,
                            $request,
                            $contentTypeSchema,
                            $sourceNamespace,
                        ),
                    );
                }
            }
        }

        yield new File($pathPrefix, 'Operation\\' . $operation->className . 'Test', $stmt->addStmt($class)->getNode());
    }

    private static function createMethod(BuilderFactory $factory, \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation $operation, OperationRequestBody|null $request, OperationResponse $response, string $sourceNamespace): Method
    {
        $responseSchemaFetch = new Node\Expr\ClassConstFetch(
            new Node\Name(
                'Schema\\' . $response->schema->className,
            ),
            new Node\Name(
                'SCHEMA_EXAMPLE_DATA',
            ),
        );

        return $factory->method('httpCode_' . $response->code . ($request === null ? '' : '_requestContentType_' . str_replace(['-', '/', '+', '.'], '_', $request->contentType)) . '_responseContentType_' . str_replace(['-', '/', '+', '.'], '_', $response->contentType))->makePublic()->setDocComment(
            new Doc(implode(PHP_EOL, [
                '/**',
                ' * @test',
                ' */',
            ]))
        )->addStmts([
            ...($response->code < 400 ? [] : [new Node\Stmt\Expression(
                new Node\Expr\StaticCall(
                    new Node\Expr\ConstFetch(
                        new Node\Name(
                            'self',
                        ),
                    ),
                    new Node\Name(
                        'expectException',
                    ),
                    [
                        new Arg(
                            new Node\Expr\ClassConstFetch(
                                new Node\Name(
                                    'ErrorSchemas\\' . $response->schema->className,
                                ),
                                new Node\Name(
                                    'class',
                                ),
                            ),
                        ),
                    ],
                ),
            )]),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable(
                        new Node\Name(
                            'response',
                        ),
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
                        new Node\Name(
                            'auth',
                        ),
                    ),
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable(
                            new Node\Name(
                                'this',
                            ),
                        ),
                        new Node\Name(
                            'prophesize',
                        ),
                        [
                            new Arg(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name(
                                        '\\' . AuthenticationInterface::class,
                                    ),
                                    new Node\Name(
                                        'class',
                                    )
                                )
                            )
                        ],
                    ),
                ),
            ),
            self::wrapShouldBeCalled(
                new Node\Expr\MethodCall(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable(
                            new Node\Name(
                                'auth'
                            ),
                        ),
                        new Node\Name(
                            'authHeader'
                        ),
                        [
                            new Arg(
                                new Node\Expr\StaticCall(
                                    new Node\Name(
                                        '\\' . Argument::class,
                                    ),
                                    new Node\Name(
                                        'any',
                                    ),
                                ),
                            ),
                        ],
                    ),
                    new Node\Name(
                        'willReturn',
                    ),
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
                        new Node\Name(
                            'browser',
                        ),
                    ),
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable(
                            new Node\Name(
                                'this',
                            ),
                        ),
                        new Node\Name(
                            'prophesize',
                        ),
                        [
                            new Arg(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name(
                                        '\\' . Browser::class,
                                    ),
                                    new Node\Name(
                                        'class',
                                    )
                                )
                            )
                        ],
                    ),
                ),
            ),
            new Node\Expr\MethodCall(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable(
                        new Node\Name(
                            'browser'
                        ),
                    ),
                    new Node\Name(
                        'withBase'
                    ),
                    [
                        new Arg(
                            new Node\Expr\StaticCall(
                                new Node\Name(
                                    '\\' . Argument::class,
                                ),
                                new Node\Name(
                                    'any',
                                ),
                            ),
                        ),
                    ],
                ),
                new Node\Name(
                    'willReturn',
                ),
                [
                    new Arg(
                        new Node\Expr\MethodCall(
                            new Node\Expr\Variable(
                                new Node\Name(
                                    'browser',
                                ),
                            ),
                            new Node\Name(
                                'reveal',
                            ),
                        ),
                    ),
                ],
            ),
            new Node\Expr\MethodCall(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable(
                        new Node\Name(
                            'browser'
                        ),
                    ),
                    new Node\Name(
                        'withFollowRedirects'
                    ),
                    [
                        new Arg(
                            new Node\Expr\StaticCall(
                                new Node\Name(
                                    '\\' . Argument::class,
                                ),
                                new Node\Name(
                                    'any',
                                ),
                            ),
                        ),
                    ],
                ),
                new Node\Name(
                    'willReturn',
                ),
                [
                    new Arg(
                        new Node\Expr\MethodCall(
                            new Node\Expr\Variable(
                                new Node\Name(
                                    'browser',
                                ),
                            ),
                            new Node\Name(
                                'reveal',
                            ),
                        ),
                    ),
                ],
            ),
            self::wrapShouldBeCalled(
                new Node\Expr\MethodCall(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable(
                            new Node\Name(
                                'browser'
                            ),
                        ),
                        new Node\Name(
                            'request'
                        ),
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
                                                if ($parameter->location === 'path') {
                                                    $items[] = '{' . $parameter->targetName . '}';
                                                }
                                            }

                                            return $items;
                                        })($operation->parameters),
                                        (static function (array $parameters): array {
                                            $items = [];
                                            foreach ($parameters as $parameter) {
                                                if ($parameter->location === 'path') {
                                                    $items[] = $parameter->example;
                                                }
                                            }

                                            return $items;
                                        })($operation->parameters),
                                        $operation->path,
                                    ) . (static function (array $parameters): string {
                                        $items = [];
                                        foreach ($parameters as $parameter) {
                                            if ($parameter->location === 'query') {
                                                $items[] = $parameter->targetName . '=' . $parameter->example;
                                            }
                                        }

                                        return count($items) > 0 ? ('?' . implode('&', $items)) : '';
                                    })($operation->parameters),
                                )
                            ),
                            new Arg(
                                new Node\Expr\StaticCall(
                                    new Node\Name(
                                        '\\' . Argument::class,
                                    ),
                                    new Node\Name(
                                        'type',
                                    ),
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
                                    new Node\Name(
                                        'any',
                                    ),
                                ) : new Node\Expr\ClassConstFetch(
                                    new Node\Name(
                                        'Schema\\' . $request->schema->className,
                                    ),
                                    new Node\Name(
                                        'SCHEMA_EXAMPLE_DATA',
                                    ),
                                ),
                            ),
                        ],
                    ),
                    new Node\Name(
                        'willReturn',
                    ),
                    [
                        new Arg(
                            new Node\Expr\FuncCall(
                                new Node\Name(
                                    '\React\Promise\resolve',
                                ),
                                [
                                    new Arg(
                                        new Node\Expr\Variable(
                                            new Node\Name(
                                                'response',
                                            ),
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
                        new Node\Name(
                            'client',
                        ),
                    ),
                    new Node\Expr\New_(
                        new Node\Name(
                            $sourceNamespace . 'Client',
                        ),
                        [
                            new Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable(
                                        new Node\Name(
                                            'auth',
                                        ),
                                    ),
                                    new Node\Name(
                                        'reveal',
                                    ),
                                ),
                            ),
                            new Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable(
                                        new Node\Name(
                                            'browser',
                                        ),
                                    ),
                                    new Node\Name(
                                        'reveal',
                                    ),
                                ),
                            ),
                        ],
                    ),
                ),
            ),
            new Node\Expr\MethodCall(
                new Node\Expr\Variable(
                    new Node\Name(
                        'client',
                    ),
                ),
                new Node\Name(
                    'call',
                ),
                [
                    new Arg(
                        new Node\Expr\ClassConstFetch(
                            new Node\Name(
                                $sourceNamespace . 'Operation\\' . $operation->className,
                            ),
                            new Node\Name(
                                'OPERATION_MATCH',
                            ),
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
                                                                new Node\Name(
                                                                    'data',
                                                                ),
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
                                                new Node\Name(
                                                    'data',
                                                ),
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
                                                        'Schema\\' . $request->schema->className,
                                                    ),
                                                    new Node\Name(
                                                        'SCHEMA_EXAMPLE_DATA',
                                                    ),
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
                                )
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
            new Node\Name(
                'shouldBeCalled',
            ),
        );
    }
}
