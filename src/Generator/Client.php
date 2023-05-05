<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Methods\ChunkCount;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers\RouterClass;
use ApiClients\Tools\OpenApiClientGenerator\Representation;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use EventSauce\ObjectHydrator\ObjectMapper;
use Jawira\CaseConverter\Convert;
use NumberToWords\NumberToWords;
use PhpParser\Builder\Method;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use ReflectionClass;
use ReflectionParameter;
use Rx\Observable;
use Rx\Subject\Subject;
use Throwable;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_shift;
use function array_unique;
use function count;
use function explode;
use function implode;
use function str_replace;
use function strpos;
use function trim;
use function ucfirst;

use const PHP_EOL;

final class Client
{
    /**
     * @return iterable<File>
     */
    public static function generate(Configuration $configuration, string $pathPrefix, string $namespace, Representation\Client $client, Routers $routers): iterable
    {
        $operations = [];
        foreach ($client->paths as $path) {
            $operations = [...$operations, ...$path->operations];
        }

        $factory = new BuilderFactory();
        $stmt = $factory->namespace(trim($namespace, '\\'));

        $class = $factory->class('Client')->implement(new Node\Name('ClientInterface'))->makeFinal()->addStmt(
            $factory->property('authentication')->setType('\\' . AuthenticationInterface::class)->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('browser')->setType('\\' . Browser::class)->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('requestSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('responseSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('router')->setType('array')->setDefault([])->makePrivate(),
        )->addStmt(
            $factory->property('operations')->setType('Operations')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('webHooks')->setType('WebHooks')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('hydrators')->setType('Hydrators')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new Param('authentication'))->setType('\\' . AuthenticationInterface::class)
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'authentication'
                    ),
                    new Node\Expr\Variable('authentication'),
                )
            )->addParam(
                (new Param('browser'))->setType('\\' . Browser::class)
            )->addStmt((static function (Representation\Client $client): Node\Expr {
                $assignExpr = new Node\Expr\Variable('browser');

                if ($client->baseUrl !== null) {
                    $assignExpr = new Node\Expr\MethodCall(
                        $assignExpr,
                        'withBase',
                        [
                            new Arg(
                                new Node\Scalar\String_($client->baseUrl),
                            ),
                        ],
                    );
                }

                return new Node\Expr\Assign(new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'browser'
                ), new Node\Expr\MethodCall(
                    $assignExpr,
                    'withFollowRedirects',
                    [
                        new Arg(
                            new Node\Expr\ConstFetch(new Node\Name('false')),
                        ),
                    ],
                ));
            })($client))->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'requestSchemaValidator'
                    ),
                    new Node\Expr\New_(
                        new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                        [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                                new Node\Name('VALIDATE_AS_REQUEST'),
                            )),
                        ]
                    ),
                )
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'responseSchemaValidator'
                    ),
                    new Node\Expr\New_(
                        new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                        [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                                new Node\Name('VALIDATE_AS_RESPONSE'),
                            )),
                        ]
                    ),
                )
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'hydrators'
                    ),
                    new Node\Expr\New_(
                        new Node\Name('Hydrators'),
                        []
                    ),
                )
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'operations'
                    ),
                    new Node\Expr\New_(
                        new Node\Name('Operations'),
                        [
                            new Arg(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'browser'
                                ),
                                false,
                                false,
                                [],
                                new Node\Identifier('browser'),
                            ),
                            new Arg(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'authentication'
                                ),
                                false,
                                false,
                                [],
                                new Node\Identifier('authentication'),
                            ),
                            new Arg(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'requestSchemaValidator'
                                ),
                                false,
                                false,
                                [],
                                new Node\Identifier('requestSchemaValidator'),
                            ),
                            new Arg(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'responseSchemaValidator'
                                ),
                                false,
                                false,
                                [],
                                new Node\Identifier('responseSchemaValidator'),
                            ),
                            new Arg(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'hydrators'
                                ),
                                false,
                                false,
                                [],
                                new Node\Identifier('hydrators'),
                            ),
                        ],
                    ),
                )
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'webHooks'
                    ),
                    new Node\Expr\New_(
                        new Node\Name('WebHooks'),
                        [
                            new Node\Arg(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'requestSchemaValidator'
                            )),
                            new Node\Arg(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'hydrators'
                            )),
                        ]
                    ),
                )
            )
        );

        if ($configuration->entryPoints->call) {
            $class->addStmt(
                $factory->method('call')->makePublic()->setDocComment(
                    new Doc(implode(PHP_EOL, [
                        '// phpcs:disable',
                        '/**',
                        ' * @return ' . (static function (array $operations): string {
                            $count = count($operations);
                            $lastItem = $count - 1;
                            $left = '';
                            $right = '';
                            for ($i = 0; $i < $count; $i++) {
                                $returnType = implode('|', [
                                    ...($operations[$i]->matchMethod === 'STREAM' ? ['iterable<string>'] : []),
                                    ...array_map(static fn(string $className): string => strpos($className, '\\') === 0 ? $className : 'Schema\\' . $className, array_unique($operations[$i]->returnType)),
                                ]);
                                $returnType = ($operations[$i]->matchMethod === 'LIST' ? 'iterable<' . $returnType . '>' : $returnType);
                                if ($i !== $lastItem) {
                                    $left .= '($call is Operation\\' . $operations[$i]->classNameSanitized . '::OPERATION_MATCH ? ' . $returnType . ' : ';
                                } else {
                                    $left .= $returnType;
                                }

                                $right .= ')';
                            }

                            return $left . $right;
                        })($operations),
                        ' */',
                        '// phpcs:enable',
                    ]))
                )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))->addStmts([
                    new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Node\Expr\Variable('result'),
                            new Node\Expr\FuncCall(
                                new Node\Name('\React\Async\await'),
                                [
                                    new Node\Arg(
                                        new Node\Expr\MethodCall(
                                            new Node\Expr\Variable('this'),
                                            new Node\Name('callAsync'),
                                            [
                                                new Node\Arg(new Node\Expr\Variable('call')),
                                                new Node\Arg(new Node\Expr\Variable('params')),
                                            ]
                                        )
                                    ),
                                ],
                            ),
                        ),
                    ),
                    new Node\Stmt\If_(
                        new Node\Expr\Instanceof_(
                            new Node\Expr\Variable('result'),
                            new Node\Name('\\' . Observable::class)
                        ),
                        [
                            'stmts' => [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Assign(
                                        new Node\Expr\Variable('result'),
                                        new Node\Expr\FuncCall(
                                            new Node\Name('\WyriHaximus\React\awaitObservable'),
                                            [
                                                new Arg(new Node\Expr\Variable('result')),
                                            ],
                                        )
                                    ),
                                ),
                            ],
                        ],
                    ),
                    new Node\Stmt\Return_(
                        new Node\Expr\Variable('result'),
                    ),
                ])
            );
        }

        $sortedOperations = [];
        foreach ($client->paths as $path) {
            foreach ($path->operations as $operation) {
                if ($operation->path === '/') {
                    $operationPath = [''];
                } else {
                    $operationPath = explode('/', $operation->path);
                }

                $operationPathCount = count($operationPath);

                if (!array_key_exists($operation->matchMethod, $sortedOperations)) {
                    $sortedOperations[$operation->matchMethod] = [];
                }

                if (!array_key_exists($operationPathCount, $sortedOperations[$operation->matchMethod])) {
                    $sortedOperations[$operation->matchMethod][$operationPathCount] = [
                        'operations' => [],
                        'paths' => [],
                    ];
                }

                $sortedOperations[$operation->matchMethod][$operationPathCount] = self::traverseOperationPaths($sortedOperations[$operation->matchMethod][$operationPathCount], $operationPath, $operation, $path);
            }
        }

        if ($configuration->entryPoints->call) {
            $chunkCountClasses = [];
            $operationsIfs = [];
            foreach ($sortedOperations as $method => $ops) {
                $opsTmts = [];
                foreach ($ops as $chunkCount => $moar) {
                    $chunkCountClasses[] = $cc = new ChunkCount(
                        'Router\\' . (new Convert($method))->toPascal() . '\\' . (new Convert(NumberToWords::transformNumber('en', $chunkCount)))->toPascal(),
                        self::traverseOperations($namespace, $moar['operations'], $moar['paths'], 0, $routers),
                    );

                    $opsTmts[] = [
                        new Node\Expr\BinaryOp\Identical(
                            new Node\Expr\Variable('pathChunksCount'),
                            new Node\Scalar\LNumber($chunkCount),
                        ),
                        [
                            new Node\Stmt\If_(
                                new Node\Expr\BinaryOp\Equal(
                                    new Node\Expr\FuncCall(
                                        new Node\Name('\array_key_exists'),
                                        [
                                            new Arg(new Node\Expr\ClassConstFetch(
                                                new Node\Name($cc->className),
                                                new Node\Name('class'),
                                            )),
                                            new Arg(new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                'router'
                                            )),
                                        ],
                                    ),
                                    new Node\Expr\ConstFetch(new Node\Name('false'))
                                ),
                                [
                                    'stmts' => [
                                        new Node\Stmt\Expression(
                                            new Node\Expr\Assign(
                                                new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                                    new Node\Expr\Variable('this'),
                                                    'router'
                                                ), new Node\Expr\ClassConstFetch(
                                                    new Node\Name($cc->className),
                                                    new Node\Name('class'),
                                                )),
                                                new Node\Expr\New_(
                                                    new Node\Name($cc->className),
                                                    [
                                                        new Arg(
                                                            new Node\Expr\PropertyFetch(
                                                                new Node\Expr\Variable('this'),
                                                                'browser'
                                                            ),
                                                            false,
                                                            false,
                                                            [],
                                                            new Node\Identifier('browser'),
                                                        ),
                                                        new Arg(
                                                            new Node\Expr\PropertyFetch(
                                                                new Node\Expr\Variable('this'),
                                                                'authentication'
                                                            ),
                                                            false,
                                                            false,
                                                            [],
                                                            new Node\Identifier('authentication'),
                                                        ),
                                                        new Arg(
                                                            new Node\Expr\PropertyFetch(
                                                                new Node\Expr\Variable('this'),
                                                                'requestSchemaValidator'
                                                            ),
                                                            false,
                                                            false,
                                                            [],
                                                            new Node\Identifier('requestSchemaValidator'),
                                                        ),
                                                        new Arg(
                                                            new Node\Expr\PropertyFetch(
                                                                new Node\Expr\Variable('this'),
                                                                'responseSchemaValidator'
                                                            ),
                                                            false,
                                                            false,
                                                            [],
                                                            new Node\Identifier('responseSchemaValidator'),
                                                        ),
                                                        new Arg(
                                                            new Node\Expr\PropertyFetch(
                                                                new Node\Expr\Variable('this'),
                                                                'hydrators'
                                                            ),
                                                            false,
                                                            false,
                                                            [],
                                                            new Node\Identifier('hydrators'),
                                                        ),
                                                    ],
                                                ),
                                            ),
                                        ),
                                    ],
                                ],
                            ),
                            new Node\Stmt\Return_(
                                new Expr\MethodCall(
                                    new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        'router'
                                    ), new Node\Expr\ClassConstFetch(
                                        new Node\Name($cc->className),
                                        new Node\Name('class'),
                                    )),
                                    new Node\Name(
                                        'call',
                                    ),
                                    [
                                        ...(static function (array $variables): iterable {
                                            foreach ($variables as $variable) {
                                                yield new Arg(
                                                    new Expr\Variable($variable),
                                                );
                                            }
                                        })([
                                            'call',
                                            'params',
                                            'pathChunks',
                                        ]),
                                    ],
                                ),
                            ),
                        ],
                    ];
                }

                $operationsIfs[] = [
                    new Node\Expr\BinaryOp\Identical(
                        new Node\Expr\Variable('method'),
                        new Node\Scalar\String_($method),
                    ),
                    (static function (array $opsTmts): array {
                        $first = array_shift($opsTmts);
                        $elseIfs = [];

                        foreach ($opsTmts as $opsTmt) {
                            $elseIfs[] = new Node\Stmt\ElseIf_(...$opsTmt);
                        }

                        return [
                            new Node\Stmt\If_(
                                $first[0],
                                [
                                    'stmts' => $first[1],
                                    'elseifs' => $elseIfs,
                                ],
                            ),
                        ];
                    })($opsTmts),
                ];
            }

            $firstOperationsIfs = array_shift($operationsIfs);
            $operationsIf = new Node\Stmt\If_(
                $firstOperationsIfs[0],
                [
                    'stmts' => $firstOperationsIfs[1],
                    'elseifs' => (static function (array $operationsIfs): array {
                        $elseIfs = [];

                        foreach ($operationsIfs as $operationsIf) {
                            $elseIfs[] = new Node\Stmt\ElseIf_(...$operationsIf);
                        }

                        return $elseIfs;
                    })($operationsIfs),
                ],
            );

            $class->addStmt(
                $factory->method('callAsync')->makePublic()->setDocComment(
                    new Doc(implode(PHP_EOL, [
                        '// phpcs:disable',
                        '/**',
                        ' * @return ' . (static function (array $operations): string {
                            $count = count($operations);
                            $lastItem = $count - 1;
                            $left = '';
                            $right = '';
                            for ($i = 0; $i < $count; $i++) {
                                $returnType = implode('|', [
                                    ...($operations[$i]->matchMethod === 'STREAM' ? ['\\' . Observable::class . '<string>'] : []),
                                    ...array_map(static fn(string $className): string => strpos($className, '\\') === 0 ? $className : 'Schema\\' . $className, array_unique($operations[$i]->returnType)),
                                ]);
                                $returnType = ($operations[$i]->matchMethod === 'LIST' ? '\\' . Observable::class . '<' . $returnType . '>' : $returnType);
                                if ($i !== $lastItem) {
                                    $left .= '($call is Operation\\' . $operations[$i]->classNameSanitized . '::OPERATION_MATCH ? \\' . PromiseInterface::class . '<' . $returnType . '> : ';
                                } else {
                                    $left .= '\\' . PromiseInterface::class . '<' . $returnType . '>';
                                }

                                $right .= ')';
                            }

                            return $left . $right;
                        })($operations),
                        ' */',
                        '// phpcs:enable',
                    ]))
                )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))->addStmt(
                    new Node\Expr\Assign(
                        new Node\Expr\Array_([
                            new Node\Expr\ArrayItem(
                                new Node\Expr\Variable('method'),
                            ),
                            new Node\Expr\ArrayItem(
                                new Node\Expr\Variable('path'),
                            ),
                        ], [
                            'kind' => Node\Expr\Array_::KIND_SHORT,
                        ]),
                        new Node\Expr\FuncCall(
                            new Node\Name('explode'),
                            [
                                new Arg(
                                    new Node\Scalar\String_(' '),
                                ),
                                new Arg(
                                    new Node\Expr\Variable('call'),
                                ),
                            ],
                        )
                    )
                )->addStmt(
                    new Node\Expr\Assign(
                        new Node\Expr\Variable('pathChunks'),
                        new Node\Expr\FuncCall(
                            new Node\Name('explode'),
                            [
                                new Arg(
                                    new Node\Scalar\String_('/'),
                                ),
                                new Arg(
                                    new Node\Expr\Variable('path'),
                                ),
                            ],
                        )
                    )
                )->addStmt(
                    new Node\Expr\Assign(
                        new Node\Expr\Variable('pathChunksCount'),
                        new Node\Expr\FuncCall(
                            new Node\Name('count'),
                            [
                                new Arg(
                                    new Node\Expr\Variable('pathChunks'),
                                ),
                            ],
                        )
                    )
                )->addStmt($operationsIf)->addStmt(
                    new Node\Stmt\Throw_(
                        new Node\Expr\New_(
                            new Node\Name('\InvalidArgumentException')
                        )
                    )
                )
            );
        }

        if ($configuration->entryPoints->operations) {
            $class->addStmt(
                $factory->method('operations')->makePublic()->setReturnType('OperationsInterface')->addStmt(new Node\Stmt\Return_(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'operations'
                    ),
                ))
            );
        }

        if ($configuration->entryPoints->webHooks) {
            $class->addStmt(
                $factory->method('webHooks')->makePublic()->setReturnType('\\' . WebHooksInterface::class)->addStmt(new Node\Stmt\Return_(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'webHooks'
                    ),
                ))
            );
        }

        yield new File($pathPrefix, 'Client', $stmt->addStmt($class)->getNode());

        $sharedProperties  = [
            $factory->property('requestSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate(),
            $factory->property('responseSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate(),
            $factory->property('hydrators')->setType($namespace . 'Hydrators')->makeReadonly()->makePrivate(),
            $factory->property('browser')->setType('\\' . Browser::class)->makeReadonly()->makePrivate(),
            $factory->property('authentication')->setType('\\' . AuthenticationInterface::class)->makeReadonly()->makePrivate(),
        ];
        $sharedConstructor = $factory->method('__construct')->makePublic()->addParam(
            $factory->param('requestSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')
        )->addParam(
            $factory->param('responseSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')
        )->addParam(
            $factory->param('hydrators')->setType($namespace . 'Hydrators')
        )->addParam(
            $factory->param('browser')->setType('\\' . Browser::class)
        )->addParam(
            $factory->param('authentication')->setType('\\' . AuthenticationInterface::class)
        )->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'requestSchemaValidator'
                ),
                new Node\Expr\Variable('requestSchemaValidator'),
            )
        )->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'responseSchemaValidator'
                ),
                new Node\Expr\Variable('responseSchemaValidator'),
            ),
        )->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'hydrators'
                ),
                new Node\Expr\Variable('hydrators'),
            ),
        )->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'browser'
                ),
                new Node\Expr\Variable('browser'),
            ),
        )->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'authentication'
                ),
                new Node\Expr\Variable('authentication'),
            ),
        );

        foreach ($routers->get() as $router) {
            yield from self::createRouter(
                $pathPrefix,
                $namespace,
                $router,
                $routers,
                $sharedConstructor,
                $sharedProperties,
            );
        }

        foreach ($chunkCountClasses as $chunkCountClass) {
            yield from self::createRouterChunkSize(
                $pathPrefix,
                $namespace,
                $chunkCountClass,
                $sharedConstructor,
                $sharedProperties,
            );
        }
    }

    /**
     * @param array<mixed> $operations
     * @param array<mixed> $operationPath
     *
     * @return array<Node>
     */
    private static function traverseOperationPaths(array $operations, array &$operationPath, Representation\Operation $operation, Representation\Path $path): array
    {
        if (count($operationPath) === 0) {
            $operations['operations'][] = [
                'operation' => $operation,
                'path' => $path,
            ];

            return $operations;
        }

        $chunk = array_shift($operationPath);
        if (! array_key_exists($chunk, $operations['paths'])) {
            $operations['paths'][$chunk] = [
                'operations' => [],
                'paths' => [],
            ];
        }

        $operations['paths'][$chunk] = self::traverseOperationPaths($operations['paths'][$chunk], $operationPath, $operation, $path);

        return $operations;
    }

    /**
     * @param array<Representation\Operation> $operations
     * @param array<Representation\Path>      $paths
     *
     * @return array<Node>
     */
    private static function traverseOperations(string $namespace, array $operations, array $paths, int $level, Routers $routers): array
    {
        $nonArgumentPathChunks = [];
        foreach (array_keys($paths) as $pathChunk) {
            if (strpos($pathChunk, '{') === 0) {
                continue;
            }

            $nonArgumentPathChunks[] = new Node\Expr\ArrayItem(new Node\Scalar\String_($pathChunk));
        }

        $ifs = [];
        foreach ($operations as $operation) {
            $ifs[] = [
                new Node\Expr\BinaryOp\Equal(
                    new Node\Expr\Variable('call'),
                    new Node\Scalar\String_($operation['operation']->matchMethod . ' ' . $operation['operation']->path),
                ),
                static::callOperation($routers, $namespace, ...$operation),
            ];
        }

        foreach ($paths as $pathChunk => $path) {
            $ifs[] = [
                new Node\Expr\BinaryOp\Equal(
                    new Node\Expr\ArrayDimFetch(
                        new Node\Expr\Variable('pathChunks'),
                        new Node\Scalar\LNumber($level),
                    ),
                    new Node\Scalar\String_($pathChunk),
                ),
                self::traverseOperations($namespace, $path['operations'], $path['paths'], $level + 1, $routers),
            ];
        }

        if (count($ifs) === 0) {
            return [];
        }

        $elfseIfs = [];
        $baseIf   = array_shift($ifs);
        foreach ($ifs as $if) {
            $elfseIfs[] = new Node\Stmt\ElseIf_($if[0], $if[1]);
        }

        return [
            new Node\Stmt\If_(
                $baseIf[0],
                [
                    'stmts' => $baseIf[1],
                    'elseifs' => $elfseIfs,
                ],
            ),
        ];
    }

    /**
     * @return array<Node>
     */
    private static function callOperation(Routers $routers, string $namespace, Representation\Operation $operation, Representation\Path $path): array
    {
        $operationClassname = 'Operator\\' . Utils::className(str_replace('/', '\\', $operation->className));

        $router =  $routers->add(
            $operation->method,
            $operation->group,
            $operation->name,
            [
                new Node\Stmt\Expression(new Node\Expr\Assign(
                    new Node\Expr\Variable('arguments'),
                    new Node\Expr\Array_(),
                )),
                ...(static function (array $params): iterable {
                    foreach ($params as $param) {
                        yield new Node\Stmt\If_(
                            new Expr\BinaryOp\Identical(
                                new Expr\FuncCall(
                                    new Node\Name('array_key_exists'),
                                    [
                                        new Arg(new Node\Scalar\String_($param->targetName)),
                                        new Arg(new Node\Expr\Variable('params')),
                                    ],
                                ),
                                new Expr\ConstFetch(
                                    new Node\Name(
                                        'false'
                                    )
                                ),
                            ),
                            [
                                'stmts' => [
                                    new Node\Stmt\Throw_(
                                        new Node\Expr\New_(
                                            new Node\Name('\InvalidArgumentException'),
                                            [
                                                new Arg(
                                                    new Node\Scalar\String_('Missing mandatory field: ' . $param->targetName)
                                                ),
                                            ],
                                        ),
                                    ),
                                ],
                            ],
                        );
                        yield new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                new Node\Expr\ArrayDimFetch(
                                    new Node\Expr\Variable('arguments'),
                                    new Node\Scalar\String_($param->targetName),
                                ),
                                new Node\Expr\ArrayDimFetch(
                                    new Node\Expr\Variable('params'),
                                    new Node\Scalar\String_($param->targetName),
                                ),
                            ),
                        );
                        yield new Node\Stmt\Unset_([
                            new Node\Expr\ArrayDimFetch(
                                new Node\Expr\Variable('params'),
                                new Node\Scalar\String_($param->targetName),
                            ),
                        ]);
                    }
                })($operation->parameters),
                ...(count(array_filter((new ReflectionClass($namespace . $operationClassname))->getConstructor()->getParameters(), static fn (ReflectionParameter $parameter): bool => $parameter->name === 'responseSchemaValidator' || $parameter->name === 'hydrator')) > 0 ? [
                    new Node\Stmt\If_(
                        new Node\Expr\BinaryOp\Equal(
                            new Node\Expr\FuncCall(
                                new Node\Name('\array_key_exists'),
                                [
                                    new Arg(new Node\Expr\ClassConstFetch(
                                        new Node\Name('Hydrator\\' . $path->hydrator->className),
                                        new Node\Name('class'),
                                    )),
                                    new Arg(new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        'hydrator'
                                    )),
                                ],
                            ),
                            new Node\Expr\ConstFetch(new Node\Name('false'))
                        ),
                        [
                            'stmts' => [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Assign(
                                        new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            'hydrator'
                                        ), new Node\Expr\ClassConstFetch(
                                            new Node\Name('Hydrator\\' . $path->hydrator->className),
                                            new Node\Name('class'),
                                        )),
                                        new Node\Expr\MethodCall(
                                            new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                'hydrators'
                                            ),
                                            'getObjectMapper' . ucfirst($path->hydrator->methodName),
                                        )
                                    ),
                                ),
                            ],
                        ]
                    ),
                ] : []),
                ...($operation->matchMethod !== 'LIST' ? self::makeCall($namespace, $operation, $path, $operationClassname, static fn (Expr $expr): Node\Stmt\Return_ => new Node\Stmt\Return_($expr)) : [
                    new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Node\Expr\Variable('stream'),
                            new Node\Expr\New_(
                                new Node\Name('\\' . Subject::class),
                            ),
                        ),
                    ),
                    new Node\Stmt\Expression(
                        new Node\Expr\StaticCall(
                            new Node\Name('\\' . Loop::class),
                            new Node\Name('futureTick'),
                            [
                                new Arg(
                                    new Node\Expr\FuncCall(
                                        new Node\Name('\React\Async\async'),
                                        [
                                            new Arg(
                                                new Node\Expr\Closure([
                                                    'stmts' => [
                                                        new Node\Stmt\TryCatch([
                                                            new Node\Stmt\Expression(
                                                                new Node\Expr\Assign(
                                                                    new Expr\ArrayDimFetch(
                                                                        new Expr\Variable('arguments'),
                                                                        new Node\Scalar\String_($operation->metaData['listOperation']['key']),
                                                                    ),
                                                                    new Node\Scalar\LNumber($operation->metaData['listOperation']['initialValue']),
                                                                ),
                                                            ),
                                                            new Node\Stmt\Do_(
                                                                new Node\Expr\BinaryOp\Greater(
                                                                    new Node\Expr\Variable('itemCount'),
                                                                    new Node\Scalar\LNumber(0),
                                                                ),
                                                                [
                                                                    new Node\Stmt\Expression(
                                                                        new Node\Expr\Assign(
                                                                            new Node\Expr\Variable('itemCount'),
                                                                            new Node\Scalar\LNumber(0),
                                                                        ),
                                                                    ),
                                                                    ...self::makeCall(
                                                                        $namespace,
                                                                        $operation,
                                                                        $path,
                                                                        $operationClassname,
                                                                        static fn (Expr $expr): Node\Stmt\Foreach_ => new Node\Stmt\Foreach_(
                                                                            new Expr\FuncCall(
                                                                                new Node\Name('\WyriHaximus\React\awaitObservable'),
                                                                                [
                                                                                    new Arg(
                                                                                        new Expr\MethodCall(
                                                                                            new Expr\StaticCall(
                                                                                                new Node\Name('\\' . Observable::class),
                                                                                                new Node\Name('fromPromise'),
                                                                                                [
                                                                                                    new Arg($expr),
                                                                                                ],
                                                                                            ),
                                                                                            new Node\Name('mergeAll'),
                                                                                        )
                                                                                    ),
                                                                                ],
                                                                            ),
                                                                            new Expr\Variable('item'),
                                                                            [
                                                                                'stmts' => [
                                                                                    new Node\Stmt\Expression(
                                                                                        new Expr\MethodCall(
                                                                                            new Node\Expr\Variable('stream'),
                                                                                            new Node\Name('onNext'),
                                                                                            [
                                                                                                new Arg(
                                                                                                    new Expr\Variable('item'),
                                                                                                ),
                                                                                            ],
                                                                                        ),
                                                                                    ),
                                                                                    new Node\Stmt\Expression(
                                                                                        new Expr\PostInc(
                                                                                            new Node\Expr\Variable('itemCount'),
                                                                                        ),
                                                                                    ),
                                                                                ],
                                                                            ],
                                                                        ),
                                                                    ),
                                                                    new Node\Stmt\Expression(
                                                                        new Expr\PostInc(
                                                                            new Expr\ArrayDimFetch(
                                                                                new Expr\Variable('arguments'),
                                                                                new Node\Scalar\String_($operation->metaData['listOperation']['key']),
                                                                            ),
                                                                        ),
                                                                    ),
                                                                ],
                                                            ),
                                                            new Node\Stmt\Expression(
                                                                new Expr\MethodCall(
                                                                    new Node\Expr\Variable('stream'),
                                                                    new Node\Name('onCompleted'),
                                                                ),
                                                            ),
                                                        ], [
                                                            new Node\Stmt\Catch_(
                                                                [
                                                                    new Node\Name('\\' . Throwable::class),
                                                                ],
                                                                new Expr\Variable('throwable'),
                                                                [
                                                                    new Node\Stmt\Expression(
                                                                        new Expr\MethodCall(
                                                                            new Node\Expr\Variable('stream'),
                                                                            new Node\Name('onError'),
                                                                            [
                                                                                new Arg(
                                                                                    new Expr\Variable('throwable'),
                                                                                ),
                                                                            ],
                                                                        ),
                                                                    ),
                                                                ],
                                                            ),
                                                        ]),
                                                    ],
                                                    'uses' => [
                                                        new Node\Expr\Variable('requestBodyData'),
                                                        new Node\Expr\Variable('stream'),
                                                    ],
                                                    'returnType' => new Node\Name('void'),
                                                ]),
                                            ),
                                        ]
                                    ),
                                ),
                            ],
                        ),
                    ),
                    new Node\Stmt\Return_(
                        new Expr\FuncCall(
                            new Node\Name('\React\Promise\resolve'),
                            [
                                new Arg(
                                    new Node\Expr\Variable('stream'),
                                ),
                            ],
                        ),
                    ),
                ]),
            ],
        );

        return [
            new Node\Stmt\If_(
                new Node\Expr\BinaryOp\Equal(
                    new Node\Expr\FuncCall(
                        new Node\Name('\array_key_exists'),
                        [
                            new Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name($router->class),
                                new Node\Name('class'),
                            )),
                            new Arg(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'router'
                            )),
                        ],
                    ),
                    new Node\Expr\ConstFetch(new Node\Name('false'))
                ),
                [
                    'stmts' => [
                        new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'router'
                                ), new Node\Expr\ClassConstFetch(
                                    new Node\Name($router->class),
                                    new Node\Name('class'),
                                )),
                                new Node\Expr\New_(
                                    new Node\Name($router->class),
                                    [
                                        new Arg(new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            'requestSchemaValidator'
                                        )),
                                        new Arg(new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            'responseSchemaValidator'
                                        )),
                                        new Arg(new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            'hydrators'
                                        )),
                                        new Arg(new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            'browser'
                                        )),
                                        new Arg(new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            'authentication'
                                        )),
                                    ],
                                ),
                            ),
                        ),
                    ],
                ],
            ),
            new Node\Stmt\Return_(
                new Expr\MethodCall(
                    new Node\Expr\ArrayDimFetch(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'router'
                        ),
                        new Node\Expr\ClassConstFetch(
                            new Node\Name($router->class),
                            new Node\Name('class'),
                        ),
                    ),
                    new Node\Name(
                        $router->method,
                    ),
                    [
                        new Arg(
                            new Node\Expr\Variable(
                                new Node\Name(
                                    'params',
                                ),
                            ),
                        ),
                    ],
                ),
            ),
        ];
    }

    /**
     * @return array<Node>
     */
    private static function makeCall(string $namespace, Representation\Operation $operation, Representation\Path $path, string $operationClassname, callable $calWrap): array
    {
        return [
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\Variable('operator'),
                new Node\Expr\New_(
                    new Node\Name($operationClassname),
                    [
                        new Arg(new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'browser'
                        )),
                        new Arg(new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'authentication'
                        )),
                        ...(count($operation->requestBody) > 0 ? [
                            new Arg(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'requestSchemaValidator'
                            )),
                        ] : []),
                        ...(count(array_filter((new ReflectionClass($namespace . $operationClassname))->getConstructor()->getParameters(), static fn (ReflectionParameter $parameter): bool => $parameter->name === 'responseSchemaValidator' || $parameter->name === 'hydrator')) > 0 ? [
                            new Arg(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'responseSchemaValidator'
                            )),
                            new Arg(new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'hydrator'
                            ), new Node\Expr\ClassConstFetch(
                                new Node\Name('Hydrator\\' . $path->hydrator->className),
                                new Node\Name('class'),
                            ))),
                        ] : []),
                    ],
                )
            )),
            $calWrap(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable('operator'),
                    'call',
                    [
                        ...(static function (array $params): iterable {
                            foreach ($params as $param) {
                                yield new Arg(new Node\Expr\ArrayDimFetch(new Node\Expr\Variable(new Node\Name('arguments')), new Node\Scalar\String_($param->targetName)));
                            }
                        })($operation->parameters),
                        ...(count($operation->requestBody) > 0 ? [
                            new Arg(new Node\Expr\Variable(new Node\Name('params')))
                        ] : []),
                    ],
                )
            ),
        ];
    }

    /**
     * @param array<Node\Stmt\Property> $properties
     *
     * @return iterable<File>
     */
    private static function createRouter(string $pathPrefix, string $namespace, RouterClass $router, Routers $routers, Method $constructor, array $properties): iterable
    {
        $className = $routers->createClassName($router->method, $router->group, '')->class;
        $factory   = new BuilderFactory();
        $stmt      = $factory->namespace(trim(Utils::dirname($namespace . $className), '\\'));
        $class     = $factory->class(trim(Utils::basename($className), '\\'))->makeFinal()->addStmt(
            $factory->property('hydrator')->setType('array')->setDefault([])->makePrivate()->setDocComment(new Doc(implode(PHP_EOL, [
                '/**',
                ' * @var array<class-string, \\' . ObjectMapper::class . '>',
                ' */',
            ]))),
        )->addStmts($properties)->addStmt(
            $constructor,
        );

        foreach ($router->methods as $method) {
            $class->addStmt(
                $factory->method($method->name)->makePublic()->addParam(
                    (new Param('params'))->setType('array'),
                )->addStmts($method->nodes)
            );
        }

        yield new File($pathPrefix, $className, $stmt->addStmt($class)->getNode());
    }

    /**
     * @param array<Node\Stmt\Property> $properties
     *
     * @return iterable<File>
     */
    private static function createRouterChunkSize(string $pathPrefix, string $namespace, ChunkCount $chunkCount, Method $constructor, array $properties): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim(Utils::dirname($namespace . $chunkCount->className), '\\'));

        $class = $factory->class(trim(Utils::basename($chunkCount->className), '\\'))->makeFinal()->addStmt(
            $factory->property('router')->setType('array')->setDefault([])->makePrivate(),
        )->addStmts(
            $properties
        )->addStmt(
            $constructor,
        );

        $class->addStmt(
            $factory->method('call')->makePublic()->addParams([
                ...(static function (array $params): iterable {
                    foreach ($params as $param => $type) {
                        yield (new Param($param))->setType($type);
                    }
                })([
                    'call' => 'string',
                    'params' => 'array',
                    'pathChunks' => 'array',
                ]),
            ])->addStmts($chunkCount->nodes)->addStmt(
                new Node\Stmt\Throw_(
                    new Node\Expr\New_(
                        new Node\Name('\InvalidArgumentException')
                    )
                )
            )
        );

        yield new File($pathPrefix, $chunkCount->className, $stmt->addStmt($class)->getNode());
    }
}
