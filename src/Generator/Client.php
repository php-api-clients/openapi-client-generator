<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\File;
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
                    ' * @return ' . (function (string $namespace, array $operations): string {
                        $count = count($operations);
                        $lastItem = $count - 1;
                        $left = '';
                        $right = '';
                        for ($i = 0; $i < $count; $i++) {
                            $returnType = implode('|', array_map(static fn (string $className): string => strpos($className, '\\') === 0 ? $className : $namespace . 'Schema\\' . $className, array_unique($operations[$i]->returnType)));
                            if ($i !== $lastItem) {
                                $left .= '($call is ' . $namespace . 'Operation\\' . $operations[$i]->classNameSanitized . '::OPERATION_MATCH ? ' . $returnType . ' : ';
                            } else {
                                $left .= $returnType;
                            }
                            $right .= ')';
                        }
                        return $left . $right;
                    })($namespace, $operations),
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

        $class->addStmt(
            $factory->method('callAsync')->makePublic()->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @return ' . (function (string $namespace, array $operations): string {
                        $count = count($operations);
                        $lastItem = $count - 1;
                        $left = '';
                        $right = '';
                        for ($i = 0; $i < $count; $i++) {
                            $returnType = implode('|', array_map(static fn (string $className): string => strpos($className, '\\') === 0 ? $className : $namespace . 'Schema\\' . $className, array_unique($operations[$i]->returnType)));
                            if ($i !== $lastItem) {
                                $left .= '($call is ' . $namespace . 'Operation\\' . $operations[$i]->classNameSanitized . '::OPERATION_MATCH ? ' . '\\' . PromiseInterface::class . '<' . $returnType . '>' . ' : ';
                            } else {
                                $left .= '\\' . PromiseInterface::class . '<' . $returnType . '>';
                            }
                            $right .= ')';
                        }
                        return $left . $right;
                    })($namespace, $operations),
                    ' */',
                ]))
            )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))->addStmt(new Node\Stmt\Switch_(
                new Node\Expr\Variable('call'),
                iterator_to_array((function (string $namespace, array $paths) use ($factory): iterable {
                    foreach ($paths as $path) {
                        foreach ($path->operations as $operation) {
                            $operationClassname = $namespace . 'Operation\\' . Utils::className(str_replace('/', '\\', $operation->className));
                            yield new Node\Stmt\Case_(
                                new Node\Expr\ClassConstFetch(new Node\Name($operationClassname), 'OPERATION_MATCH'),
                                [
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
                                                            new Node\Expr\FuncCall(
                                                                new Node\Name('\array_push'),
                                                                [
                                                                    new Arg(new Node\Expr\Variable(new Node\Name('requestBodyData'))),
                                                                    new Arg(new Node\Expr\Variable(new Node\Name('param'))),
                                                                ],
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
                                                        new Node\Name($namespace . 'Hydrator\\' . $path->hydrator->className),
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
                                                            new Node\Name($namespace . 'Hydrator\\' . $path->hydrator->className),
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
                                                        new Node\Name($namespace . 'Hydrator\\' . $path->hydrator->className),
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
                                                'returnType' => count($operation->returnType) > 0 ? new Node\UnionType(array_map(static fn(string $object): Node\Name => new Node\Name(strpos($object, '\\') === 0 ? $object : $namespace . 'Schema\\' . $object), array_unique($operation->returnType))) : null,
                                            ]))
                                        ]
                                    )),
                                    new Node\Stmt\Break_(),
                                ]
                            );
                            //                        yield new Node\Stmt\Echo_([new Node\Scalar\String_('/**' . @var_export($operationCall, true) . '*/')]);
                        }
                    }
                })($namespace, $client->paths))
            ))->addStmt(
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
}
