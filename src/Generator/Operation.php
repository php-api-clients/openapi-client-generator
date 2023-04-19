<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\Reader;
use cebe\openapi\spec\Schema;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use React\Http\Browser;
use React\Stream\ReadableStreamInterface;
use RingCentral\Psr7\Request;
use RuntimeException;
use Rx\Observable;
use Rx\Scheduler\ImmediateScheduler;
use Rx\Subject\Subject;
use Throwable;

use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function ltrim;
use function rtrim;
use function strlen;
use function strtolower;

use const PHP_EOL;

final class Operation
{
    /**
     * @return iterable<Node>
     */
    public static function generate(string $pathPrefix, string $namespace, \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation $operation, Hydrator $hydrator, ThrowableSchema $throwableSchemaRegistry, Configuration $configuration): iterable
    {
        $noHydrator = true;
        $factory    = new BuilderFactory();
        $stmt       = $factory->namespace(ltrim(Utils::dirname($namespace . '\\Operation\\' . $operation->className), '\\'));

        $class = $factory->class(Utils::className(ltrim(Utils::basename($operation->className), '\\')))->makeFinal()->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'OPERATION_ID',
                        new Node\Scalar\String_(
                            $operation->operationId
                        )
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'OPERATION_MATCH',
                        new Node\Scalar\String_(
                            $operation->matchMethod . ' ' . $operation->path, // Deal with the query
                        )
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'METHOD',
                        new Node\Scalar\String_(
                            $operation->method,
                        )
                    ),
                ],
                Class_::MODIFIER_PRIVATE
            )
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'PATH',
                        new Node\Scalar\String_(
                            $operation->path, // Deal with the query
                        )
                    ),
                ],
                Class_::MODIFIER_PRIVATE
            )
        );
        if (count($operation->requestBody) > 0) {
            $class->addStmt(
                $factory->property('requestSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
            );
        }

        $constructor = $factory->method('__construct')->makePublic();

        if (count($operation->requestBody) > 0) {
            $constructor->addParam(
                (new Param('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'requestSchemaValidator'
                    ),
                    new Node\Expr\Variable('requestSchemaValidator'),
                )
            );
        }

        $requestReplaces   = [];
        $query             = [];
        $constructorParams = [];
        foreach ($operation->parameters as $parameter) {
            $paramterStmt = $factory->property($parameter->name);
            $param        = new Param($parameter->name);
            if (strlen($parameter->description) > 0) {
                $paramterStmt->setDocComment('/**' . (string) $parameter->description . '**/');
            }

            if ($parameter->type !== '') {
                $paramterStmt->setType($parameter->type);

                $param->setType($parameter->type);
            }

            $class->addStmt($paramterStmt->makePrivate());

            if ($parameter->default !== null) {
                $param->setDefault($parameter->default);
            }

            $constructorParams[] = $param;
            $constructor->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $parameter->name
                    ),
                    new Node\Expr\Variable($parameter->name),
                )
            );
            if ($parameter->location === 'path' || $parameter->location === 'query') {
                $requestReplaces['{' . $parameter->targetName . '}'] = new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    $parameter->name
                );
            }

            if ($parameter->location !== 'query') {
                continue;
            }

            $query[] = $parameter->targetName . '={' . $parameter->targetName . '}';
        }

        $requestParameters = [
            new Node\Arg(new Node\Expr\ClassConstFetch(
                new Node\Name('self'),
                new Node\Name('METHOD'),
            )),
            new Node\Arg(new Node\Expr\FuncCall(
                new Node\Name('\str_replace'),
                [
                    new Node\Expr\Array_(array_map(static fn (string $key): Node\Expr\ArrayItem => new Node\Expr\ArrayItem(new Node\Scalar\String_($key)), array_keys($requestReplaces))),
                    new Node\Expr\Array_(array_values($requestReplaces)),
                    count($query) > 0 ? new Node\Expr\BinaryOp\Concat(
                        new Node\Expr\ClassConstFetch(
                            new Node\Name('self'),
                            new Node\Name('PATH'),
                        ),
                        new Node\Scalar\String_(rtrim('?' . implode('&', $query), '?')),
                    ) : new Node\Expr\ClassConstFetch(
                        new Node\Name('self'),
                        new Node\Name('PATH'),
                    ),
                ]
            )),
        ];

        $createRequestMethod = $factory->method('createRequest')->setReturnType('\\' . RequestInterface::class)->addParam(
            $factory->param('data')->setType('array')->setDefault([])
        )->makePublic();

        foreach ($operation->requestBody as $requestBody) {
            $requestParameters[] = new Node\Expr\Array_([new Node\Expr\ArrayItem(new Node\Scalar\String_($requestBody->contentType), new Node\Scalar\String_('Content-Type'))]);
            $requestParameters[] = new Node\Expr\FuncCall(new Node\Name('json_encode'), [new Arg(new Node\Expr\Variable('data'))]);
            $createRequestMethod->addStmt(
                new Node\Stmt\Expression(new Node\Expr\MethodCall(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'requestSchemaValidator'
                    ),
                    new Node\Name('validate'),
                    [
                        new Node\Arg(new Node\Expr\Variable('data')),
                        new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\\' . Reader::class), new Node\Name('readFromJson'), [
                            new Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('Schema\\' . $requestBody->schema->className),
                                new Node\Name('SCHEMA_JSON'),
                            )),
                            new Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\\' . Schema::class),
                                new Node\Name('class'),
                            )),
                        ])),
                    ]
                ))
            );
            break;
        }

        $createRequestMethod->addStmt(
            new Node\Stmt\Return_(
                new Node\Expr\New_(
                    new Node\Name(
                        '\\' . Request::class
                    ),
                    $requestParameters
                )
            )
        );

        $returnType    = [];
        $returnTypeRaw = [];
//            $case = new Node\Stmt\Case_(
//                new Node\Scalar\LNumber($code),
//                [
//                    ...(count($contentTypeCases) > 0 ? [new Node\Stmt\Switch_(
//                        new Node\Expr\Variable('contentType'),
//                        $contentTypeCases
//                    )] : (count($redirects) > 0 ? $redirects : new Node\Stmt\Return_(new Node\Expr\Variable('response')))),
//                    new Node\Stmt\Break_()
//                ]
//            );
//            if (strlen($description) > 0) {
//                $case->setDocComment(new Doc('/**' . $description . '**/'));
//            }
//            if (count($contentTypeCases) === 0 && count($redirects) === 0) {
//                $returnType[] = $returnTypeRaw[] = '\\' . ResponseInterface::class;
//            }


        $cases = [];

        foreach ($configuration->contentType as $contentType) {
            foreach ($contentType::contentType() as $supportedContentType) {
                $caseCases = [];
                foreach ($operation->response as $contentTypeSchema) {
                    if ($supportedContentType !== $contentTypeSchema->contentType) {
                        continue;
                    }

                    $returnOrThrow = Node\Stmt\Return_::class;
                    $isError       = $contentTypeSchema->code >= 400;
                    if ($isError) {
                        $returnOrThrow = Node\Stmt\Throw_::class;
                        $throwableSchemaRegistry->add($contentTypeSchema->schema->className);
                    }

                    $object = ($isError ? 'ErrorSchemas' : 'Schema') . '\\' . $contentTypeSchema->schema->className;
                    if (! $isError) {
                        $returnType[]    = ($contentTypeSchema->schema->isArray ? '\\' . Observable::class . '<' : '') . $object . ($contentTypeSchema->schema->isArray ? '>' : '');
                        $returnTypeRaw[] = $contentTypeSchema->schema->isArray ? '\\' . Observable::class : $object;
                    }

                    $hydrate = new Node\Expr\MethodCall(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'hydrator'
                        ),
                        new Node\Name('hydrateObject'),
                        [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('Schema\\' . $contentTypeSchema->schema->className),
                                new Node\Name('class'),
                            )),
                            new Node\Arg(new Node\Expr\Variable('body')),
                        ],
                    );

                    if ($isError) {
                        $hydrate = new Node\Expr\New_(
                            new Node\Name('ErrorSchemas\\' . $contentTypeSchema->schema->className),
                            [
                                new Arg(
                                    new Node\Scalar\LNumber($contentTypeSchema->code),
                                ),
                                new Arg(
                                    $hydrate,
                                ),
                            ],
                        );
                    }

                    $validate = new Node\Stmt\Expression(new Node\Expr\MethodCall(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'responseSchemaValidator'
                        ),
                        new Node\Name('validate'),
                        [
                            new Node\Arg(new Node\Expr\Variable($contentTypeSchema->schema->isArray ? 'bodyItem' : 'body')),
                            new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), new Node\Name('readFromJson'), [
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name('Schema\\' . $contentTypeSchema->schema->className),
                                    new Node\Name('SCHEMA_JSON'),
                                ),
                                new Node\Scalar\String_('\cebe\openapi\spec\Schema'),
                            ])),
                        ]
                    ));

                    $case = new Node\Stmt\Case_(
                        new Node\Scalar\LNumber($contentTypeSchema->code),
                        [
                            $contentTypeSchema->schema->isArray ? new Node\Stmt\Foreach_(
                                new Node\Expr\Variable('body'),
                                new Node\Expr\Variable('bodyItem'),
                                [
                                    'stmts' => [$validate],
                                ],
                            ) : $validate,
                            new $returnOrThrow(
                                $contentTypeSchema->schema->isArray ? new Node\Expr\MethodCall(
                                    new Node\Expr\StaticCall(
                                        new Node\Name('\\' . Observable::class),
                                        new Node\Name('fromArray'),
                                        [
                                            new Node\Arg(new Node\Expr\Variable('body')),
                                            new Node\Arg(
                                                new Node\Expr\New_(
                                                    new Node\Name('\\' . ImmediateScheduler::class),
                                                ),
                                            ),
                                        ]
                                    ),
                                    new Node\Name('map'),
                                    [
                                        new Arg(new Node\Expr\Closure([
                                            'stmts' => [
                                                new Node\Stmt\Return_(
                                                    $hydrate,
                                                ),
                                            ],
                                            'params' => [new Node\Param(new Node\Expr\Variable('body'), null, new Node\Name('array'))],
                                            'returnType' => $object,
                                        ])),
                                    ]
                                ) : $hydrate,
                            ),
                        ],
                    );

                    if (strlen($contentTypeSchema->description) > 0) {
                        $case->setDocComment(new Doc('/**' . PHP_EOL . ' * ' . $contentTypeSchema->description . PHP_EOL . '**/'));
                    }

                    $caseCases[] = $case;
                }

                if (count($caseCases) <= 0) {
                    continue;
                }

                $cases[] = new Node\Stmt\Case_(
                    new Node\Scalar\String_($supportedContentType),
                    [
                        new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                new Node\Expr\Variable(
                                    'body',
                                ),
                                $contentType::parse(
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\MethodCall(
                                            new Node\Expr\Variable(
                                                'response',
                                            ),
                                            'getBody',
                                        ),
                                        'getContents',
                                    ),
                                ),
                            ),
                        ),
                        new Node\Stmt\Switch_(
                            new Node\Expr\Variable('code'),
                            $caseCases,
                        ),
                        new Node\Stmt\Break_(),
                    ],
                );
            }
        }

        $redirectCases = [];
        foreach ($operation->redirect as $redirect) {
            $redirects = [];

            if ($operation->matchMethod === 'STREAM') {
                $redirects[] = new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\Variable('stream'),
                        new Node\Expr\New_(
                            new Node\Name('\\' . Subject::class),
                        ),
                    ),
                );
                $redirects[] = new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        new Node\Expr\MethodCall(
                            new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'browser'
                            ),
                            new Node\Name('requestStreaming'),
                            [
                                new Arg(new Node\Scalar\String_('GET')),
                                new Arg(
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\Variable('response'),
                                        new Node\Name('getHeaderLine'),
                                        [
                                            new Arg(new Node\Scalar\String_('location')),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                        new Node\Name('then'),
                        [
                            new Arg(new Node\Expr\Closure(
                                [
                                    'stmts' => [
                                        new Node\Stmt\Expression(
                                            new Node\Expr\Assign(
                                                new Node\Expr\Variable('body'),
                                                new Node\Expr\MethodCall(
                                                    new Node\Expr\Variable('response'),
                                                    new Node\Name('getBody'),
                                                ),
                                            ),
                                        ),
                                        new Node\Stmt\If_(
                                            new Node\Expr\BooleanNot(
                                                new Node\Expr\BinaryOp\BooleanAnd(
                                                    new Node\Expr\Instanceof_(
                                                        new Node\Expr\Variable('body'),
                                                        new Node\Name('\\' . StreamInterface::class)
                                                    ),
                                                    new Node\Expr\Instanceof_(
                                                        new Node\Expr\Variable('body'),
                                                        new Node\Name('\\' . ReadableStreamInterface::class)
                                                    ),
                                                )
                                            ),
                                            [
                                                'stmts' => [
                                                    new Node\Stmt\Expression(
                                                        new Node\Expr\MethodCall(
                                                            new Node\Expr\Variable('stream'),
                                                            new Node\Name('onError'),
                                                            [
                                                                new Arg(new Node\Expr\New_(
                                                                    new Node\Name('\\' . RuntimeException::class)
                                                                )),
                                                            ],
                                                        ),
                                                    ),
                                                    new Node\Stmt\Return_(),
                                                ],
                                            ],
                                        ),
                                        new Node\Stmt\Expression(
                                            new Node\Expr\MethodCall(
                                                new Node\Expr\Variable('body'),
                                                new Node\Name('on'),
                                                [
                                                    new Arg(new Node\Scalar\String_('data')),
                                                    new Arg(new Node\Expr\Closure(
                                                        [
                                                            'stmts' => [
                                                                new Node\Stmt\Expression(
                                                                    new Node\Expr\MethodCall(
                                                                        new Node\Expr\Variable('stream'),
                                                                        new Node\Name('onNext'),
                                                                        [
                                                                            new Arg(new Node\Expr\Variable('data')),
                                                                        ],
                                                                    ),
                                                                ),
                                                            ],
                                                            'params' => [
                                                                $factory->param('data')->setType('string')->getNode(),
                                                            ],
                                                            'uses' => [
                                                                new Node\Expr\ClosureUse(
                                                                    new Node\Expr\Variable('stream'),
                                                                ),
                                                            ],
                                                            'static' => true,
                                                            'returnType' => new Node\Name('void'),
                                                        ],
                                                    )),
                                                ],
                                            ),
                                        ),
                                        new Node\Stmt\Expression(
                                            new Node\Expr\MethodCall(
                                                new Node\Expr\Variable('body'),
                                                new Node\Name('on'),
                                                [
                                                    new Arg(new Node\Scalar\String_('close')),
                                                    new Arg(new Node\Expr\Closure(
                                                        [
                                                            'stmts' => [
                                                                new Node\Stmt\Expression(
                                                                    new Node\Expr\MethodCall(
                                                                        new Node\Expr\Variable('stream'),
                                                                        new Node\Name('onCompleted'),
                                                                    ),
                                                                ),
                                                            ],
                                                            'uses' => [
                                                                new Node\Expr\ClosureUse(
                                                                    new Node\Expr\Variable('stream'),
                                                                ),
                                                            ],
                                                            'static' => true,
                                                            'returnType' => new Node\Name('void'),
                                                        ],
                                                    )),
                                                ],
                                            ),
                                        ),
                                        new Node\Stmt\Expression(
                                            new Node\Expr\MethodCall(
                                                new Node\Expr\Variable('body'),
                                                new Node\Name('on'),
                                                [
                                                    new Arg(new Node\Scalar\String_('error')),
                                                    new Arg(new Node\Expr\Closure(
                                                        [
                                                            'stmts' => [
                                                                new Node\Stmt\Expression(
                                                                    new Node\Expr\MethodCall(
                                                                        new Node\Expr\Variable('stream'),
                                                                        new Node\Name('onError'),
                                                                        [
                                                                            new Arg(new Node\Expr\Variable('error')),
                                                                        ],
                                                                    ),
                                                                ),
                                                            ],
                                                            'params' => [
                                                                $factory->param('error')->setType('\\' . Throwable::class)->getNode(),
                                                            ],
                                                            'uses' => [
                                                                new Node\Expr\ClosureUse(
                                                                    new Node\Expr\Variable('stream'),
                                                                ),
                                                            ],
                                                            'static' => true,
                                                            'returnType' => new Node\Name('void'),
                                                        ],
                                                    )),
                                                ],
                                            ),
                                        ),
                                    ],
                                    'params' => [
                                        $factory->param('response')->setType('\\' . ResponseInterface::class)->getNode(),
                                    ],
                                    'uses' => [
                                        new Node\Expr\ClosureUse(
                                            new Node\Expr\Variable('stream'),
                                        ),
                                    ],
                                    'static' => true,
                                    'returnType' => new Node\Name('void'),
                                ],
                            )),
                        ],
                    ),
                );
                $redirects[] = new Node\Stmt\Return_(new Node\Expr\Variable('stream'));

                $returnType[]    = '\\' . Observable::class . '<string>';
                $returnTypeRaw[] = '\\' . Observable::class;
            } else {
                $arrayItems              = [];
                $arrayItems['code: int'] = new Node\Expr\ArrayItem(
                    new Node\Scalar\LNumber($redirect->code),
                    new Node\Scalar\String_('code'),
                );
                foreach ($redirect->headers as $header) {
                    $arrayItems[strtolower($header->name) . ': string'] = new Node\Expr\ArrayItem(
                        new Node\Expr\MethodCall(
                            new Node\Expr\Variable('response'),
                            new Node\Name('getHeaderLine'),
                            [
                                new Arg(new Node\Scalar\String_($header->name)),
                            ],
                        ),
                        new Node\Scalar\String_(strtolower($header->name))
                    );
                }

                $returnType[]    = 'array{' . implode(',', array_keys($arrayItems)) . '}';
                $returnTypeRaw[] = 'array';
                $redirects[]     = new Node\Stmt\Return_(new Node\Expr\Array_(array_values($arrayItems)));
            }

            $redirectCase = new Node\Stmt\Case_(
                new Node\Scalar\LNumber($redirect->code),
                $redirects,
            );
            if (strlen($redirect->description) > 0) {
                $redirectCase->setDocComment(new Doc('/**' . PHP_EOL . ' * ' . $redirect->description . PHP_EOL . '**/'));
            }

            $redirectCases[] = $redirectCase;
        }

        $createResponseMethod = $factory->method('createResponse')->makePublic();

        if (count($cases) > 0 || count($redirectCases) > 0) {
            $createResponseMethod->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\Variable(
                        'code',
                    ),
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable(
                            'response',
                        ),
                        'getStatusCode',
                    ),
                ),
            );
        }

        if (count($cases) > 0) {
            $noHydrator = false;
            $createResponseMethod->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\Array_([
                        new Node\Expr\ArrayItem(
                            new Node\Expr\Variable('contentType'),
                        ),
                    ], [
                        'kind' => Node\Expr\Array_::KIND_SHORT,
                    ]),
                    new Node\Expr\FuncCall(
                        new Node\Name('explode'),
                        [
                            new Arg(new Node\Scalar\String_(';')),
                            new Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('response'),
                                    'getHeaderLine',
                                    [
                                        new Arg(new Node\Scalar\String_('Content-Type')),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            );
            $createResponseMethod->addStmt(
                new Node\Stmt\Switch_(
                    new Node\Expr\Variable('contentType'),
                    $cases,
                ),
            );
        }

        if (count($redirectCases) > 0) {
            $createResponseMethod->addStmt(
                new Node\Stmt\Switch_(
                    new Node\Expr\Variable('code'),
                    $redirectCases,
                ),
            );
        }

        if (count($cases) > 0 || count($redirectCases) > 0) {
            $createResponseMethod->addStmt(
                new Node\Stmt\Throw_(
                    new Node\Expr\New_(
                        new Node\Name('\\' . RuntimeException::class),
                        [new Arg(new Node\Scalar\String_('Unable to find matching response code and content type'))]
                    )
                )
            );
        } else {
            $createResponseMethod->addStmt(new Node\Stmt\Return_(new Node\Expr\Variable('response')));
            $returnType[] = $returnTypeRaw[] = '\\' . ResponseInterface::class;
        }

        $returnTypeRaw = array_unique($returnTypeRaw);
        if (count($returnTypeRaw) === 0) {
            $returnTypeRaw[] = 'mixed';
        }

        $createResponseMethod->setReturnType(
            new Node\UnionType(array_map(static fn (string $object): Node\Name => new Node\Name($object), $returnTypeRaw))
        );

        if (count($returnType) > 0) {
            $createResponseMethod->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @return ' . implode('|', array_unique($returnType)),
                    ' */',
                ]))
            );
        }

        $createResponseMethod->addParam(
            $factory->param('response')->setType('\\' . ResponseInterface::class)
        );

        if ($noHydrator === false) {
            $class->addStmt(
                $factory->property('responseSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
            )->addStmt(
                $factory->property('hydrator')->setType('Hydrator\\' . $hydrator->className)->makeReadonly()->makePrivate()
            );

            $constructor->addParam(
                (new Param('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'responseSchemaValidator'
                    ),
                    new Node\Expr\Variable('responseSchemaValidator'),
                )
            )->addParam(
                (new Param('hydrator'))->setType('Hydrator\\' . $hydrator->className)
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'hydrator'
                    ),
                    new Node\Expr\Variable('hydrator'),
                )
            );
        }

        if ($operation->matchMethod === 'STREAM') {
            $class->addStmt(
                $factory->property('browser')->setType('\\' . Browser::class)->makeReadonly()->makePrivate()
            );
            $constructor->addParam(
                (new Param('browser'))->setType('\\' . Browser::class)
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'browser'
                    ),
                    new Node\Expr\Variable('browser'),
                )
            );
        }

        $constructor->addParams($constructorParams);

        $class->addStmt($constructor);
        $class->addStmt($createRequestMethod);
        $class->addStmt($createResponseMethod);

        yield new File($pathPrefix, 'Operation\\' . $operation->className, $stmt->addStmt($class)->getNode());
    }
}
