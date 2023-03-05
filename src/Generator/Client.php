<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Client\Github\Schema\WebhookLabelEdited\Changes\Name;
use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use cebe\openapi\spec\PathItem;
use EventSauce\ObjectHydrator\ObjectMapper;
use Jawira\CaseConverter\Convert;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\Request;
use Rx\Observable;
use Twig\Node\Expression\Binary\AndBinary;
use Twig\Node\Expression\Binary\OrBinary;

final class Client
{
    /**
     * @param string $namespace
     * @return iterable
     */
    public static function generate(string $namespace, \ApiClients\Tools\OpenApiClientGenerator\Representation\Client $client): iterable
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
            $factory->property('hydrator')->setType('array')->setDefault([])->makePrivate()->setDocComment(new Doc(implode(PHP_EOL, [
                '/**',
                ' * @var array<class-string, \\' . ObjectMapper::class . '>',
                ' */',
            ]))),
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
            )->addStmt((static function (\ApiClients\Tools\OpenApiClientGenerator\Representation\Client $client): Node\Expr {
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
                ), $assignExpr);
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
                            ))
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
                            ))
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

        $class->addStmt(
            $factory->method('call')->makePublic()->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @return ' . (function (array $operations): string {
                        $count = count($operations);
                        $lastItem = $count - 1;
                        $left = '';
                        $right = '';
                        for ($i = 0; $i < $count; $i++) {
                            $returnType = implode('|', array_map(static fn (string $className): string => strpos($className, '\\') === 0 ? $className : 'Schema\\' . $className, array_unique($operations[$i]->returnType)));
                            if ($i !== $lastItem) {
                                $left .= '($call is ' . 'Operation\\' . $operations[$i]->classNameSanitized . '::OPERATION_MATCH ? ' . $returnType . ' : ';
                            } else {
                                $left .= $returnType;
                            }
                            $right .= ')';
                        }
                        return $left . $right;
                    })($operations),
                    ' */',
                ]))
            )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))->addStmt(new Node\Stmt\Return_(
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
                )
            ))
        );

        $sortedOperations = [];
        foreach ($client->paths as $path) {
            foreach ($path->operations as $operation) {
                if ($operation->path === '/') {
                    $operationPath = [''];
                } else {
                    $operationPath = explode('/', $operation->path);
                }
                $operationPathCount = count($operationPath);

                if (!array_key_exists($operation->method, $sortedOperations)) {
                    $sortedOperations[$operation->method] = [];
                }
                if (!array_key_exists($operationPathCount, $sortedOperations[$operation->method])) {
                    $sortedOperations[$operation->method][$operationPathCount] = [
                        'operations' => [],
                        'paths' => [],
                    ];
                }

                $sortedOperations[$operation->method][$operationPathCount] = self::traverseOperationPaths($sortedOperations[$operation->method][$operationPathCount], $operationPath, $operation, $path);
            }
        }


//        new Node\Stmt\Switch_(
//            new Node\Expr\Variable('method'),
//            iterator_to_array((function (array $sortedOperations) use ($factory): iterable {
//                foreach ($sortedOperations as $method => $operation) {
//                    yield new Node\Stmt\Case_(
//                        new Node\Scalar\String_($method),
//                        [
//                            ...self::traverseOperations($operation['operations'], $operation['paths'], 0),
//                            new Node\Stmt\Break_(),
//                        ],
//                    );
//                }
//            })($sortedOperations))
//        )

        $operationsIfs = [];
        foreach ($sortedOperations as $method => $ops) {
            $opsTmts = [];
            foreach ($ops as $chunkCount => $moar) {
                $opsTmts[] = [
                    new Node\Expr\BinaryOp\Identical(
                        new Node\Expr\Variable('pathChunksCount'),
                        new Node\Scalar\LNumber($chunkCount),
                    ),
                    self::traverseOperations($moar['operations'], $moar['paths'], 0),
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
                        )
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
                    '/**',
                    ' * @return ' . (function (array $operations): string {
                        $count = count($operations);
                        $lastItem = $count - 1;
                        $left = '';
                        $right = '';
                        for ($i = 0; $i < $count; $i++) {
                            $returnType = implode('|', array_map(static fn (string $className): string => strpos($className, '\\') === 0 ? $className : 'Schema\\' . $className, array_unique($operations[$i]->returnType)));
                            if ($i !== $lastItem) {
                                $left .= '($call is ' . 'Operation\\' . $operations[$i]->classNameSanitized . '::OPERATION_MATCH ? ' . '\\' . PromiseInterface::class . '<' . $returnType . '>' . ' : ';
                            } else {
                                $left .= '\\' . PromiseInterface::class . '<' . $returnType . '>';
                            }
                            $right .= ')';
                        }
                        return $left . $right;
                    })($operations),
                    ' */',
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

        $class->addStmt(
            $factory->method('webHooks')->makePublic()->setReturnType('\\' . WebHooksInterface::class)->addStmt(new Node\Stmt\Return_(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'webHooks'
                ),
            ))
        );

        yield new File($namespace . 'Client', $stmt->addStmt($class)->getNode());
    }

    private static function traverseOperationPaths(array $operations, array &$operationPath, \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation $operation, Path $path): array
    {
        if (count($operationPath) === 0) {
            $operations['operations'][] = [
                'operation' => $operation,
                'path' => $path,
            ];

            return $operations;
        }

        $chunk = array_shift($operationPath);
        if (!array_key_exists($chunk, $operations['paths'])) {
            $operations['paths'][$chunk] = [
                'operations' => [],
                'paths' => [],
            ];
        }

        $operations['paths'][$chunk] = self::traverseOperationPaths($operations['paths'][$chunk], $operationPath, $operation, $path);

        return $operations;
    }

    private static function traverseOperations(array $operations, array $paths, int $level): array
    {
        $nonArgumentPathChunks = [];
        foreach ($paths as $pathChunk => $_) {
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
                    new Node\Scalar\String_($operation['operation']->method . ' ' . $operation['operation']->path),
                ),
                static::callOperation(...$operation),
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
                self::traverseOperations($path['operations'], $path['paths'], $level + 1),
            ];
        }

        if (count($ifs) === 0) {
            return [];
        }

        $elfseIfs = [];
        $baseIf = array_shift($ifs);
        foreach ($ifs as $if) {
            $elfseIfs[] = new Node\Stmt\ElseIf_($if[0], $if[1]);
        }

        return [new Node\Stmt\If_(
            $baseIf[0],
            [
                'stmts' => $baseIf[1],
                'elseifs' => $elfseIfs,
            ],
        )];
    }

    private static function callOperation(\ApiClients\Tools\OpenApiClientGenerator\Representation\Operation $operation, Path $path): array
    {
        $operationClassname = 'Operation\\' . Utils::className(str_replace('/', '\\', $operation->className));
        return [
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\Variable('requestBodyData'),
                new Node\Expr\Array_(),
            )),
            new Node\Stmt\Foreach_(new Node\Expr\FuncCall(
                new Node\Name('\array_keys'),
                [
                    new Arg(new Node\Expr\Variable(new Node\Name('params'))),
                ],
            ), new Node\Expr\Variable(new Node\Name('param')), [
                'stmts' => [
                    new Node\Stmt\If_(
                        new Node\Expr\BinaryOp\NotEqual(
                            new Node\Expr\FuncCall(
                                new Node\Name('\in_array'),
                                [
                                    new Arg(new Node\Expr\Variable(new Node\Name('param'))),
                                    new Arg(new Node\Expr\Array_(
                                        iterator_to_array((function (array $params): iterable {
                                            foreach ($params as $param) {
                                                yield new Node\Expr\ArrayItem(new Node\Scalar\String_($param->name));
                                            }
                                        })($operation->parameters)),
                                    )),
                                ],
                            ),
                            new Node\Expr\ConstFetch(new Node\Name('false'))
                        ),
                        [
                            'stmts' => [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Assign(
                                        new Node\Expr\ArrayDimFetch(
                                            new Node\Expr\Variable('requestBodyData'),
                                            new Node\Expr\Variable('param'),
                                        ),
                                        new Node\Expr\ArrayDimFetch(
                                            new Node\Expr\Variable('params'),
                                            new Node\Expr\Variable('param'),
                                        ),
                                    ),
                                ),
                            ],
                        ]
                    ),
                ],
            ]),
            ...(implode('|', $operation->returnType) === ('\\' . ResponseInterface::class) ? [] : [new Node\Stmt\If_(
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
            )]),
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\Variable('operation'),
                new Node\Expr\New_(
                    new Node\Name($operationClassname),
                    [
                        ...(count($operation->requestBody) > 0 ? [
                            new Arg(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'requestSchemaValidator'
                            )),
                        ] : []),
                        ...(implode('|', $operation->returnType) === ('\\' . ResponseInterface::class) ? [] : [
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
                        ]),
                        ...iterator_to_array((function (array $params): iterable {
                            foreach ($params as $param) {
                                yield new Arg(new Node\Expr\ArrayDimFetch(new Node\Expr\Variable(new Node\Name('params')), new Node\Scalar\String_($param->name)));
                            }
                        })($operation->parameters)),
                    ],
                )
            )),
            new Node\Stmt\Expression(new Node\Expr\Assign(new Node\Expr\Variable('request'), new Node\Expr\MethodCall(new Node\Expr\Variable('operation'), 'createRequest', [
                new Arg(new Node\Expr\Variable(new Node\Name('requestBodyData')))
            ]))),
            new Node\Stmt\Return_(new Node\Expr\MethodCall(
                new Node\Expr\MethodCall(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'browser'
                    ),
                    'request',
                    [
                        new Node\Arg(new Node\Expr\MethodCall(new Node\Expr\Variable('request'), 'getMethod'),),
                        new Node\Arg(new Node\Expr\MethodCall(new Node\Expr\Variable('request'), 'getUri'),),
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
                                                    'authentication'
                                                ),
                                                'authHeader',
                                            ),
                                        ),
                                    ]
                                ),
                                'getHeaders'
                            ),
                        ),
                        new Node\Arg(new Node\Expr\MethodCall(new Node\Expr\Variable('request'), 'getBody'),),
                    ]
                ),
                'then',
                [
                    new Arg(new Node\Expr\Closure([
                        'stmts' => [
                            new Node\Stmt\Return_(new Node\Expr\MethodCall(new Node\Expr\Variable('operation'), 'createResponse', [
                                new Node\Expr\Variable('response')
                            ])),
                        ],
                        'params' => [
                            new Node\Param(new Node\Expr\Variable('response'), null, new Node\Name('\\' . ResponseInterface::class))
                        ],
                        'uses' => [
                            new Node\Expr\Variable('operation'),
                        ],
                        'returnType' => count($operation->returnType) > 0 ? new Node\UnionType(array_map(static fn(string $object): Node\Name => new Node\Name(strpos($object, '\\') === 0 ? $object : 'Schema\\' . $object), array_unique($operation->returnType))) : null,
                    ]))
                ]
            )),
        ];
    }
}
