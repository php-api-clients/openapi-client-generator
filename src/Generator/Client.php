<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\SchemaRegistry;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use cebe\openapi\spec\PathItem;
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
     * @return iterable<Node>
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public static function generate(string $namespace, array $clients, SchemaRegistry $schemaRegistry): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace(rtrim($namespace, '\\'));

        $class = $factory->class('Client')->makeFinal()->addStmt(
            $factory->property('authentication')->setType('\\' . AuthenticationInterface::class)->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('browser')->setType('\\' . Browser::class)->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('requestSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('responseSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
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
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'browser'
                    ),
                    new Node\Expr\New_(
                        new Node\Name('\\' . Browser::class),
                        []
                    ),
                )
            )->addStmt(
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
            )
        );

        $operationCalls = [];
        $callReturnTypes = [];

        foreach ($clients as $operationGroup => $operations) {
            $cn = str_replace('/', '\\', '\\' . $namespace . 'Operation/' . $operationGroup);
            $casedOperationgroup = lcfirst($operationGroup);
            foreach ($operations as $operationOperation => $operationDetails) {
                $returnType = [];
                foreach ($operationDetails['operation']->responses as $code => $spec) {
                    $contentTypeCases = [];
                    foreach ($spec->content as $contentType => $contentTypeSchema) {
                        $fallbackName = 'Operation\\' . $operationGroup . '\\Response\\' . (new Convert(str_replace('/', '\\', $contentType) . '\\H' . $code ))->toPascal();
                        $object = '\\' . $namespace . 'Schema\\' . $schemaRegistry->get($contentTypeSchema->schema, $fallbackName);
                        $callReturnTypes[] = ($contentTypeSchema->schema->type === 'array' ? '\\' . Observable::class . '<' : '') . $object . ($contentTypeSchema->schema->type === 'array' ? '>' : '');
                        $contentTypeCases[] = $returnType[] = $contentTypeSchema->schema->type === 'array' ? '\\' . Observable::class : $object;
                    }
                    if (count($contentTypeCases) === 0) {
                        $returnType[] = $callReturnTypes[] = 'int';
                    }
                }
                $operationCalls[] = [
                    'operationGroupMethod' => $casedOperationgroup,
                    'operationMethod' => lcfirst($operationOperation),
                    'className' => str_replace('/', '\\', '\\' . $namespace . 'Operation\\' . $operationDetails['class']),
                    'params' => iterator_to_array((function (array $operationDetails): iterable {
                        foreach ($operationDetails['operation']->parameters as $parameter) {
                            yield $parameter->name;
                        }
                    })($operationDetails)),
                    'returnType' => $returnType,
                ];
            }
            $class->addStmt(
                $factory->method($casedOperationgroup)->setReturnType($cn)->addStmt(
                    new Node\Stmt\Return_(
                        new Node\Expr\New_(
                            new Node\Name(
                                $cn
                            ),
                            [
                                new Node\Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'requestSchemaValidator'
                                )),
                                new Node\Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'responseSchemaValidator'
                                )),
                            ]
                        )
                    )
                )->makePublic()
            );
        }

        $class->addStmt(
            $factory->method('call')->makePublic()->setReturnType(
                new Node\Name('\\' . PromiseInterface::class)
            )->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @return \\' . PromiseInterface::class . '<' . implode('|', array_unique($callReturnTypes)) . '>',
                    ' */',
                ]))
            )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))->addStmt(new Node\Stmt\Switch_(
                new Node\Expr\Variable('call'),
                iterator_to_array((function (iterable $operationCalls) use ($factory): iterable {
                    foreach ($operationCalls as $operationCall) {
                        yield new Node\Stmt\Case_(
                            new Node\Expr\ClassConstFetch(new Node\Name($operationCall['className']), 'OPERATION_MATCH'),
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
                                                                    yield new Node\Expr\ArrayItem(new Node\Scalar\String_($param));
                                                                }
                                                            })($operationCall['params'])),
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
                                new Node\Stmt\Expression(new Node\Expr\Assign(
                                    new Node\Expr\Variable('operation'),
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\MethodCall(
                                            new Node\Expr\Variable('this'),
                                            $operationCall['operationGroupMethod'],
                                            [],
                                        ),
                                        $operationCall['operationMethod'],
                                        iterator_to_array((function (array $params): iterable {
                                            foreach ($params as $param) {
                                                yield new Arg(new Node\Expr\ArrayDimFetch(new Node\Expr\Variable(new Node\Name('params')), new Node\Scalar\String_($param)));
                                            }
                                        })($operationCall['params'])),
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
                                            'returnType' => count($operationCall['returnType']) > 0 ? new Node\UnionType(array_map(static fn (string $object): Node\Name => new Node\Name($object), array_unique($operationCall['returnType']))) : null,
                                        ]))
                                    ]
                                )),
                                new Node\Stmt\Break_(),
                            ]
                        );
//                        yield new Node\Stmt\Echo_([new Node\Scalar\String_('/**' . @var_export($operationCall, true) . '*/')]);
                    }
                })($operationCalls))
            ))
        );

        yield new File($namespace . '\\' . 'Client', $stmt->addStmt($class)->getNode());
    }
}
