<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClient\Utils\Response\Header;
use ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\OperationArray;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\Reader;
use cebe\openapi\spec\Schema;
use Jawira\CaseConverter\Convert;
use League\Uri\UriTemplate;
use NumberToWords\NumberToWords;
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

use function array_map;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function ksort;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

use const PHP_EOL;

final class Operation
{
    /** @return iterable<File> */
    public static function generate(string $pathPrefix, \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation $operation, Hydrator $hydrator, ThrowableSchema $throwableSchemaRegistry, Configuration $configuration): iterable
    {
        $noHydrator = true;
        $factory    = new BuilderFactory();
        $stmt       = $factory->namespace($operation->className->namespace->source);

        $class = $factory->class($operation->className->className)->makeFinal()->addStmt(
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
        if (count($operation->requestBody) > 0) {
            $class->addStmt(
                $factory->property('requestSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate(),
            );
        }

        $constructor = $factory->method('__construct')->makePublic();

        if (count($operation->requestBody) > 0) {
            $constructor->addParam(
                (new Param('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'requestSchemaValidator',
                    ),
                    new Node\Expr\Variable('requestSchemaValidator'),
                ),
            );
        }

        $requestReplaces   = [];
        $query             = [];
        $constructorParams = [];
        foreach ($operation->parameters as $parameter) {
            $paramterStmt = $factory->property($parameter->name);
            $param        = new Param($parameter->name);
            if (strlen($parameter->description) > 0) {
                $paramterStmt->setDocComment('/**' . $parameter->description . ' **/');
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
                        $parameter->name,
                    ),
                    new Node\Expr\Variable($parameter->name),
                ),
            );
            if ($parameter->location === 'path' || $parameter->location === 'query') {
                $propertyFetch                           = new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    $parameter->name,
                );
                $requestReplaces[$parameter->targetName] = $propertyFetch;
            }

            if ($parameter->location !== 'query') {
                continue;
            }

            $query[$parameter->targetName] = $parameter->targetName . ($parameter->type === 'array' ? '*' : '');
        }

        ksort($query);
        ksort($requestReplaces);

        $requestParameters = [
            new Node\Arg(
                new Node\Scalar\String_($operation->method),
            ),
            new Node\Arg(
                new Node\Expr\Cast\String_(
                    new Node\Expr\MethodCall(
                        new Node\Expr\New_(
                            new Node\Name(
                                '\\' . UriTemplate::class,
                            ),
                            [
                                new Node\Arg(
                                    count($query) > 0 ? new Node\Scalar\String_(
                                        $operation->path . '{?' . implode(',', $query) . '}',
                                    ) : new Node\Scalar\String_(
                                        $operation->path, // Deal with the query
                                    ),
                                ),
                            ],
                        ),
                        new Node\Name(
                            'expand',
                        ),
                        [
                            new Arg(
                                new Node\Expr\Array_(
                                    [
                                        ...(static function (array $requestReplaces): iterable {
                                            foreach ($requestReplaces as $key => $valueRetreival) {
                                                yield new Node\Expr\ArrayItem(
                                                    $valueRetreival,
                                                    new Node\Scalar\String_($key),
                                                );
                                            }
                                        })($requestReplaces),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ),
        ];

        $createRequestMethod = $factory->method('createRequest')->setReturnType('\\' . RequestInterface::class)->makePublic();
        if (count($operation->requestBody) > 0) {
            $createRequestMethod->addParam(
                $factory->param('data')->setType('array'),
            );
        }

        foreach ($operation->requestBody as $requestBody) {
            $requestParameters[] = new Node\Arg(new Node\Expr\Array_([new Node\Expr\ArrayItem(new Node\Scalar\String_($requestBody->contentType), new Node\Scalar\String_('Content-Type'))]));
            $requestParameters[] = new Node\Arg(new Node\Expr\FuncCall(new Node\Name('json_encode'), [new Arg(new Node\Expr\Variable('data'))]));
            $createRequestMethod->addStmt(
                new Node\Stmt\Expression(new Node\Expr\MethodCall(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'requestSchemaValidator',
                    ),
                    'validate',
                    [
                        new Node\Arg(new Node\Expr\Variable('data')),
                        new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\\' . Reader::class), 'readFromJson', [
                            new Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name($requestBody->schema->className->relative),
                                'SCHEMA_JSON',
                            )),
                            new Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\\' . Schema::class),
                                'class',
                            )),
                        ])),
                    ],
                )),
            );
            break;
        }

        $createRequestMethod->addStmt(
            new Node\Stmt\Return_(
                new Node\Expr\New_(
                    new Node\Name(
                        '\\' . Request::class,
                    ),
                    $requestParameters,
                ),
            ),
        );

        $returnType    = [];
        $returnTypeRaw = [];
        $cases         = [];

        foreach ($configuration->contentType ?? [] as $contentType) {
            foreach ($contentType::contentType() as $supportedContentType) {
                $caseCases = [];
                foreach ($operation->response as $contentTypeSchema) {
                    $scPosition = strpos($contentTypeSchema->contentType, ';');
                    if (
                        (! is_int($scPosition) && $supportedContentType !== $contentTypeSchema->contentType) ||
                        (is_int($scPosition) && $scPosition >= 0 && $supportedContentType !== substr($contentTypeSchema->contentType, 0, $scPosition))
                    ) {
                        continue;
                    }

                    if (! $contentTypeSchema->content->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
                        if ($contentTypeSchema->content->type === 'scalar') {
                            $returnType[] = $returnTypeRaw[] = $contentTypeSchema->content->payload;

                            $caseCases[] = new Node\Stmt\Case_(
                                new Node\Scalar\LNumber($contentTypeSchema->code),
                                [
                                    new Node\Stmt\Return_(
                                        new Node\Expr\Variable(
                                            'body',
                                        ),
                                    ),
                                ],
                            );
                            continue;
                        }
                    }

                    $isArray = $contentTypeSchema->content->type === 'array' || ($contentTypeSchema->content->type !== 'union' && $contentTypeSchema->content->payload->isArray);

                    $isError = $contentTypeSchema->code >= 400;

                    if ($contentTypeSchema->content->type === 'union' || $contentTypeSchema->content->type === 'array') {
                        $gotoLabels = (new Convert(Utils::cleanUpString('items_' . $supportedContentType . '_' . NumberToWords::transformNumber('en', $contentTypeSchema->code) . '_aaaaa')))->toSnake();
                        $sTmts      = [];
                        $types      = [];

                        $sTmts[] = new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                new Node\Expr\Variable('error'),
                                new Node\Expr\New_(
                                    new Node\Name('\\' . RuntimeException::class),
                                ),
                            ),
                        );

                        foreach (
                            OperationArray::uniqueSchemas(...(
                                is_array($contentTypeSchema->content->payload) ? $contentTypeSchema->content->payload : [$contentTypeSchema->content->payload]
                            )) as $item
                        ) {
                            if ($item instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
                                $sTmts[] = new Node\Stmt\TryCatch([
                                    new Node\Stmt\Expression(new Node\Expr\MethodCall(
                                        new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            'responseSchemaValidator',
                                        ),
                                        'validate',
                                        [
                                            new Node\Arg(new Node\Expr\Variable('body')),
                                            new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), 'readFromJson', [
                                                new Arg(new Node\Expr\ClassConstFetch(
                                                    new Node\Name($item->className->relative),
                                                    'SCHEMA_JSON',
                                                )),
                                                new Arg(new Node\Scalar\String_('\cebe\openapi\spec\Schema')),
                                            ])),
                                        ],
                                    )),
                                    new Node\Stmt\Return_(new Node\Expr\MethodCall(
                                        new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            'hydrator',
                                        ),
                                        'hydrateObject',
                                        [
                                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                                new Node\Name($item->className->relative),
                                                'class',
                                            )),
                                            new Node\Arg(new Node\Expr\Variable('body')),
                                        ],
                                    )),
                                ], [
                                    new Node\Stmt\Catch_(
                                        [new Node\Name('\\' . Throwable::class)],
                                        new Node\Expr\Variable('error'),
                                        [
                                            new Node\Stmt\Goto_($gotoLabels),
                                        ],
                                    ),
                                ]);
                                $sTmts[] = new Node\Stmt\Label($gotoLabels);
                                $gotoLabels++;
                                $types[] = $item->className->relative;
                            } else {
                                $sTmts[] = new Node\Stmt\If_(
                                    new Node\Expr\FuncCall(
                                        new Node\Name('\is_' . $item),
                                        [
                                            new Node\Arg(new Node\Expr\Variable('body')),
                                        ],
                                    ),
                                    [
                                        'stmts' => [
                                            new Node\Stmt\Return_(
                                                new Node\Expr\Variable('body'),
                                            ),
                                        ],
                                    ],
                                );
                                $types[] = $item;
                            }
                        }

                        $sTmts[] = new Node\Stmt\Throw_(new Node\Expr\Variable('error'));

                        if (! $isError) {
                            if ($contentTypeSchema->content->type === 'array') {
                                $returnType[]    = '\\' . Observable::class . '<' . implode('|', $types) . '>';
                                $returnTypeRaw[] = '\\' . Observable::class;
                            } else {
                                $returnType[] = $returnTypeRaw[] = implode('|', $types);
                            }
                        }

                        $tmts = $contentTypeSchema->content->type === 'array' ? [
                            new Node\Stmt\Return_(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\StaticCall(
                                        new Node\Name('\\' . Observable::class),
                                        'fromArray',
                                        [
                                            new Node\Arg(new Node\Expr\Variable('body')),
                                            new Node\Arg(
                                                new Node\Expr\New_(
                                                    new Node\Name('\\' . ImmediateScheduler::class),
                                                ),
                                            ),
                                        ],
                                    ),
                                    'map',
                                    [
                                        new Arg(new Node\Expr\Closure([
                                            'stmts' => $sTmts,
                                            'params' => [new Node\Param(new Node\Expr\Variable('body'), null, new Node\Name('array'))],
                                            'returnType' => new Node\UnionType(
                                                array_map(static fn (string $name): Node\name => new Node\Name($name), $types),
                                            ),
                                        ])),
                                    ],
                                ),
                            ),
                        ] : $sTmts;
                    } else {
                        $returnOrThrow = Node\Stmt\Return_::class;
                        if ($isError) {
                            $returnOrThrow = Node\Stmt\Throw_::class;
                            $throwableSchemaRegistry->add($contentTypeSchema->content->payload->className->relative);
                        }

                        $object = $isError ? $contentTypeSchema->content->payload->errorClassNameAliased->relative : $contentTypeSchema->content->payload->className->relative;
                        if (! $isError) {
                            $returnType[]    = ($isArray ? '\\' . Observable::class . '<' : '') . $object . ($isArray ? '>' : '');
                            $returnTypeRaw[] = $isArray ? '\\' . Observable::class : $object;
                        }

                        $validate = OperationArray::validate($contentTypeSchema->content->payload->className->relative, $isArray);
                        $hydrate  = OperationArray::hydrate($contentTypeSchema->content->payload->className->relative);

                        if ($isError) {
                            $hydrate = new Node\Expr\New_(
                                new Node\Name($contentTypeSchema->content->payload->errorClassNameAliased->relative),
                                [
                                    new Arg(
                                        is_string($contentTypeSchema->code) ? new Node\Expr\Variable('code') : new Node\Scalar\LNumber($contentTypeSchema->code),
                                    ),
                                    new Arg(
                                        $hydrate,
                                    ),
                                ],
                            );
                        }

                        $tmts = [
                            $isArray ? new Node\Stmt\Foreach_(
                                new Node\Expr\Variable('body'),
                                new Node\Expr\Variable('bodyItem'),
                                [
                                    'stmts' => [$validate],
                                ],
                            ) : $validate,
                            new $returnOrThrow(
                                $isArray ? new Node\Expr\MethodCall(
                                    new Node\Expr\StaticCall(
                                        new Node\Name('\\' . Observable::class),
                                        'fromArray',
                                        [
                                            new Node\Arg(new Node\Expr\Variable('body')),
                                            new Node\Arg(
                                                new Node\Expr\New_(
                                                    new Node\Name('\\' . ImmediateScheduler::class),
                                                ),
                                            ),
                                        ],
                                    ),
                                    'map',
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
                                    ],
                                ) : $hydrate,
                            ),
                        ];
                    }

                    $case = new Node\Stmt\Case_(
                        is_string($contentTypeSchema->code) ? null : new Node\Scalar\LNumber($contentTypeSchema->code),
                        $tmts,
                    );

                    if (strlen($contentTypeSchema->description) > 0) {
                        $case->setDocComment(new Doc('/**' . PHP_EOL . ' * ' . $contentTypeSchema->description . PHP_EOL . ' **/'));
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

        $casesWithoutContent = [];
        foreach ($operation->empty as $empty) {
            $empties = [];

            if ($operation->matchMethod === 'STREAM') {
                $empties[] = new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\Variable('stream'),
                        new Node\Expr\New_(
                            new Node\Name('\\' . Subject::class),
                        ),
                    ),
                );
                $empties[] = new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        new Node\Expr\MethodCall(
                            new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'browser',
                            ),
                            'requestStreaming',
                            [
                                new Arg(new Node\Scalar\String_('GET')),
                                new Arg(
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\Variable('response'),
                                        'getHeaderLine',
                                        [
                                            new Arg(new Node\Scalar\String_('location')),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                        'then',
                        [
                            new Arg(new Node\Expr\Closure(
                                [
                                    'stmts' => [
                                        new Node\Stmt\Expression(
                                            new Node\Expr\Assign(
                                                new Node\Expr\Variable('body'),
                                                new Node\Expr\MethodCall(
                                                    new Node\Expr\Variable('response'),
                                                    'getBody',
                                                ),
                                            ),
                                        ),
                                        new Node\Stmt\If_(
                                            new Node\Expr\BooleanNot(
                                                new Node\Expr\BinaryOp\BooleanAnd(
                                                    new Node\Expr\Instanceof_(
                                                        new Node\Expr\Variable('body'),
                                                        new Node\Name('\\' . StreamInterface::class),
                                                    ),
                                                    new Node\Expr\Instanceof_(
                                                        new Node\Expr\Variable('body'),
                                                        new Node\Name('\\' . ReadableStreamInterface::class),
                                                    ),
                                                ),
                                            ),
                                            [
                                                'stmts' => [
                                                    new Node\Stmt\Expression(
                                                        new Node\Expr\MethodCall(
                                                            new Node\Expr\Variable('stream'),
                                                            'onError',
                                                            [
                                                                new Arg(new Node\Expr\New_(
                                                                    new Node\Name('\\' . RuntimeException::class),
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
                                                'on',
                                                [
                                                    new Arg(new Node\Scalar\String_('data')),
                                                    new Arg(new Node\Expr\Closure(
                                                        [
                                                            'stmts' => [
                                                                new Node\Stmt\Expression(
                                                                    new Node\Expr\MethodCall(
                                                                        new Node\Expr\Variable('stream'),
                                                                        'onNext',
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
                                                'on',
                                                [
                                                    new Arg(new Node\Scalar\String_('close')),
                                                    new Arg(new Node\Expr\Closure(
                                                        [
                                                            'stmts' => [
                                                                new Node\Stmt\Expression(
                                                                    new Node\Expr\MethodCall(
                                                                        new Node\Expr\Variable('stream'),
                                                                        'onCompleted',
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
                                                'on',
                                                [
                                                    new Arg(new Node\Scalar\String_('error')),
                                                    new Arg(new Node\Expr\Closure(
                                                        [
                                                            'stmts' => [
                                                                new Node\Stmt\Expression(
                                                                    new Node\Expr\MethodCall(
                                                                        new Node\Expr\Variable('stream'),
                                                                        'onError',
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
                $empties[] = new Node\Stmt\Return_(new Node\Expr\Variable('stream'));

                $returnType[]    = '\\' . Observable::class . '<string>';
                $returnTypeRaw[] = '\\' . Observable::class;
            } else {
                $arrayItems = [];
                foreach ($empty->headers as $header) {
                    $arrayItems[strtolower($header->name) . ': string'] = new Node\Expr\ArrayItem(
                        new Node\Expr\New_(
                            new Node\Name('\\' . Header::class),
                            [
                                new Arg(
                                    new Node\Scalar\String_(strtolower($header->name)),
                                ),
                                new Arg(
                                    new Node\Expr\MethodCall(
                                        new Node\Expr\Variable('response'),
                                        'getHeaderLine',
                                        [
                                            new Arg(new Node\Scalar\String_($header->name)),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                    );
                }

//                'array{' . implode(',', array_keys($arrayItems)) . '}';
                $returnType[] = $returnTypeRaw[] = '\\' . WithoutBody::class;
                $empties[]    = new Node\Stmt\Return_(
                    new Node\Expr\New_(
                        new Node\Name('\\' . WithoutBody::class),
                        [
                            new Arg(
                                new Node\Scalar\LNumber($empty->code),
                            ),
                            new Arg(
                                new Node\Expr\Array_(array_values($arrayItems)),
                            ),
                        ],
                    ),
                );
            }

            $emptyCase = new Node\Stmt\Case_(
                new Node\Scalar\LNumber($empty->code),
                $empties,
            );
            if (strlen($empty->description) > 0) {
                $emptyCase->setDocComment(new Doc('/**' . PHP_EOL . ' * ' . $empty->description . PHP_EOL . ' **/'));
            }

            $casesWithoutContent[] = $emptyCase;
        }

        $createResponseMethod = $factory->method('createResponse')->makePublic();

        if (count($cases) > 0 || count($casesWithoutContent) > 0) {
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

        if (count($casesWithoutContent) > 0) {
            $createResponseMethod->addStmt(
                new Node\Stmt\Switch_(
                    new Node\Expr\Variable('code'),
                    $casesWithoutContent,
                ),
            );
        }

        if (count($cases) > 0 || count($casesWithoutContent) > 0) {
            $createResponseMethod->addStmt(
                new Node\Stmt\Throw_(
                    new Node\Expr\New_(
                        new Node\Name('\\' . RuntimeException::class),
                        [new Arg(new Node\Scalar\String_('Unable to find matching response code and content type'))],
                    ),
                ),
            );
        } else {
            $createResponseMethod->addStmt(new Node\Stmt\Return_(new Node\Expr\Variable('response')));
            $returnType[] = $returnTypeRaw[] = '\\' . ResponseInterface::class;
        }

        $returnTypeRaw = array_unique($returnTypeRaw);
        if (count($returnTypeRaw) === 0) {
            $returnTypeRaw[] = 'void';
        }

        $createResponseMethod->setReturnType(
            new Node\UnionType(array_map(static fn (string $object): Node\Name => new Node\Name($object), $returnTypeRaw)),
        );

        if (count($returnType) > 0) {
            $createResponseMethod->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @return ' . implode('|', array_unique($returnType)),
                    ' */',
                ])),
            );
        }

        $createResponseMethod->addParam(
            $factory->param('response')->setType('\\' . ResponseInterface::class),
        );

        if ($noHydrator === false) {
            $class->addStmt(
                $factory->property('responseSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate(),
            )->addStmt(
                $factory->property('hydrator')->setType($hydrator->className->relative)->makeReadonly()->makePrivate(),
            );

            $constructor->addParam(
                (new Param('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'responseSchemaValidator',
                    ),
                    new Node\Expr\Variable('responseSchemaValidator'),
                ),
            )->addParam(
                (new Param('hydrator'))->setType($hydrator->className->relative),
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'hydrator',
                    ),
                    new Node\Expr\Variable('hydrator'),
                ),
            );
        }

        if ($operation->matchMethod === 'STREAM') {
            $class->addStmt(
                $factory->property('browser')->setType('\\' . Browser::class)->makeReadonly()->makePrivate(),
            );
            $constructor->addParam(
                (new Param('browser'))->setType('\\' . Browser::class),
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'browser',
                    ),
                    new Node\Expr\Variable('browser'),
                ),
            );
        }

        $constructor->addParams($constructorParams);

        $class->addStmt($constructor);
        $class->addStmt($createRequestMethod);
        $class->addStmt($createResponseMethod);

        yield new File($pathPrefix, $operation->className->relative, $stmt->addStmt($class)->getNode());
    }
}
