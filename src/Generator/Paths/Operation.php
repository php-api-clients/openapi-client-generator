<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Paths;

use ApiClients\Tools\OpenApiClient\Utils\Response\Header;
use ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody;
use ApiClients\Tools\OpenApiClientGenerator\Contract\ContentType;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\ConfigurationPackage;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\OperationArray;
use cebe\openapi\Reader;
use cebe\openapi\spec\Schema;
use Jawira\CaseConverter\Convert;
use League\OpenAPIValidation\Schema\SchemaValidator;
use League\Uri\UriTemplate;
use NumberToWords\NumberToWords;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\File;
use OpenAPITools\Utils\Utils;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use React\Http\Browser;
use React\Http\Message\Request;
use React\Stream\ReadableStreamInterface;
use RuntimeException;
use Rx\Observable;
use Rx\Scheduler\ImmediateScheduler;
use Rx\Subject\Subject;
use Throwable;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function ksort;
use function strpos;
use function strtolower;
use function substr;

use const PHP_EOL;

final readonly class Operation
{
    /** @var array<ContentType> */
    private array $contentTypes;

    public function __construct(
        private BuilderFactory $builderFactory,
        ContentType ...$contentTypes,
    ) {
        $this->contentTypes = $contentTypes;
    }

    /** @return iterable<File> */
    public function generate(Package $package, Namespaced\Operation $operation, Namespaced\Hydrator $hydrator): iterable
    {
        $package = ConfigurationPackage::unwrap($package);

        $noHydrator = true;
        $stmt       = $this->builderFactory->namespace($operation->className->namespace->source);

        $class       = $this->builderFactory->class($operation->className->className)->makeFinal()->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'OPERATION_ID',
                        new Node\Scalar\String_(
                            $operation->operationId,
                        ),
                    ),
                ],
                1,
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
                1,
            ),
        );
        $constructor = $this->builderFactory->method('__construct')->makePublic();

        if (count($operation->requestBody) > 0) {
            $constructor->addParam(
                $this->builderFactory->param('requestSchemaValidator')->makePrivate()->setType('\\' . SchemaValidator::class),
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
            $paramterStmt = $this->builderFactory->property($parameter->name);
            $param        = new Param($parameter->name);
            if ($parameter->description !== '') {
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
                        'expand',
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

        $createRequestMethod = $this->builderFactory->method('createRequest')->setReturnType('\\' . RequestInterface::class)->makePublic();
        if (count($operation->requestBody) > 0) {
            $createRequestMethod->addParam(
                $this->builderFactory->param('data')->setType('array'),
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
                                new Node\Name($requestBody->schema->className->fullyQualified->source),
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

        foreach ($this->contentTypes as $contentType) {
            foreach ($contentType::contentType() as $supportedContentType) {
                $caseCases = [];
                foreach ($operation->response as $contentTypeSchema) {
                    $content    = $contentTypeSchema->content;
                    $scPosition = strpos($contentTypeSchema->contentType, ';');
                    if (is_int($scPosition)) {
                        if ($supportedContentType !== substr($contentTypeSchema->contentType, 0, $scPosition)) {
                            continue;
                        }
                    } elseif ($supportedContentType !== $contentTypeSchema->contentType) {
                        continue;
                    }

                    $responseCode = is_int($contentTypeSchema->code) ? $contentTypeSchema->code : (int) $contentTypeSchema->code;

                    if ($content instanceof Namespaced\Property\Type && ! $content->payload instanceof Namespaced\Schema) {
                        if ($content->type === 'scalar') {
                            $returnType[] = $returnTypeRaw[] = $content->payload;

                            $caseCases[] = new Node\Stmt\Case_(
                                new Node\Scalar\LNumber($responseCode),
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

                    $isArray = ($content instanceof Namespaced\Property\Type && ($content->type === 'array' || ($content->type !== 'union' && $content->payload instanceof Namespaced\Schema && $content->payload->isArray)))
                        || ($content instanceof Namespaced\Schema && $content->isArray);

                    $isError = $responseCode >= 400;

                    if ($content instanceof Namespaced\Property\Type && ($content->type === 'union' || $content->type === 'array')) {
                        /**
                         * Only the suffix is incremented. The label as a whole
                         * contains underscores, and incrementing a string that
                         * is not alphanumeric is deprecated.
                         */
                        $gotoLabelsPrefix = new Convert(Utils::cleanUpString('items_' . $supportedContentType . '_' . NumberToWords::transformNumber('en', $responseCode)))->toSnake();
                        $gotoLabelsSuffix = 0;
                        $gotoLabels       = $gotoLabelsPrefix . '_' . $gotoLabelsSuffix;
                        $sTmts            = [];
                        /** @var list<string> $types */
                        $types = [];

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
                                is_array($content->payload) ? $content->payload : [$content->payload]
                            )) as $item
                        ) {
                            if ($item instanceof Namespaced\Schema) {
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
                                                    new Node\Name($item->className->fullyQualified->source),
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
                                                new Node\Name($item->className->fullyQualified->source),
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
                                ++$gotoLabelsSuffix;
                                $gotoLabels = $gotoLabelsPrefix . '_' . $gotoLabelsSuffix;
                                $types[]    = $item->className->fullyQualified->source;
                            } elseif (is_string($item)) {
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

                        $sTmts[] = new Node\Stmt\Expression(new Node\Expr\Throw_(new Node\Expr\Variable('error')));

                        if (! $isError) {
                            if ($contentTypeSchema->content->type === 'array') {
                                $returnType[]    = '\\' . Observable::class . '<' . implode('|', $types) . '>';
                                $returnTypeRaw[] = '\\' . Observable::class;
                            } else {
                                $returnType[] = $returnTypeRaw[] = implode('|', $types);
                            }
                        }

                        $tmts = $content->type === 'array' ? [
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
                                                array_map(static fn (string $name): Node\Name => new Node\Name($name), $types),
                                            ),
                                        ])),
                                    ],
                                ),
                            ),
                        ] : $sTmts;
                    } elseif ($content instanceof Namespaced\Schema || $content->payload instanceof Namespaced\Schema) {
                        $schema = $content instanceof Namespaced\Schema ? $content : $content->payload;

                        $buildReturnOrThrow = static fn (Node\Expr $expr, bool $error): Node\Stmt => $error
                            ? new Node\Stmt\Expression(new Node\Expr\Throw_($expr))
                            : new Node\Stmt\Return_($expr);
                        $returnOrThrow      = static fn (Node\Expr $expr): Node\Stmt => $buildReturnOrThrow($expr, $isError);

                        $object = $isError ? $schema->errorClassNameAliased->fullyQualified->source : $schema->className->fullyQualified->source;
                        if (! $isError) {
                            $returnType[]    = ($isArray ? '\\' . Observable::class . '<\\' : '') . $object . ($isArray ? '>' : '');
                            $returnTypeRaw[] = $isArray ? '\\' . Observable::class : $object;
                        }

                        $validate          = OperationArray::validate($schema->className->fullyQualified->source, $isArray);
                        $hydrateMethodCall = OperationArray::hydrate($schema->className->fullyQualified->source);
                        $responseValue     = $isError
                            ? new Node\Expr\New_(
                                new Node\Name($schema->errorClassName->fullyQualified->source),
                                [
                                    new Arg(
                                        is_string($contentTypeSchema->code) ? new Node\Expr\Variable('code') : new Node\Scalar\LNumber($responseCode),
                                    ),
                                    new Arg(
                                        $hydrateMethodCall,
                                    ),
                                ],
                            )
                            : $hydrateMethodCall;

                        $tmts = [
                            $isArray ? new Node\Stmt\Foreach_(
                                new Node\Expr\Variable('body'),
                                new Node\Expr\Variable('bodyItem'),
                                [
                                    'stmts' => [$validate],
                                ],
                            ) : $validate,
                            $returnOrThrow(
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
                                                    $responseValue,
                                                ),
                                            ],
                                            'params' => [new Node\Param(new Node\Expr\Variable('body'), null, new Node\Name('array'))],
                                            'returnType' => new Node\Name('\\' . $object),
                                        ])),
                                    ],
                                ) : $responseValue,
                            ),
                        ];
                    } else {
                        continue;
                    }

                    $case = new Node\Stmt\Case_(
                        is_string($contentTypeSchema->code) ? null : new Node\Scalar\LNumber($contentTypeSchema->code),
                        $tmts,
                    );

                    if ($contentTypeSchema->description !== '') {
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
                                                                $this->builderFactory->param('data')->setType('string')->getNode(),
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
                                                                $this->builderFactory->param('error')->setType('\\' . Throwable::class)->getNode(),
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
                                        $this->builderFactory->param('response')->setType('\\' . ResponseInterface::class)->getNode(),
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
            if ($empty->description !== '') {
                $emptyCase->setDocComment(new Doc('/**' . PHP_EOL . ' * ' . $empty->description . PHP_EOL . ' **/'));
            }

            $casesWithoutContent[] = $emptyCase;
        }

        $createResponseMethod = $this->builderFactory->method('createResponse')->makePublic();

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
                new Node\Stmt\Expression(
                    new Node\Expr\Throw_(
                        new Node\Expr\New_(
                            new Node\Name('\\' . RuntimeException::class),
                            [new Arg(new Node\Scalar\String_('Unable to find matching response code and content type'))],
                        ),
                    ),
                ),
            );
        } else {
            $createResponseMethod->addStmt(new Node\Stmt\Return_(new Node\Expr\Variable('response')));
            $returnType[] = $returnTypeRaw[] = '\\' . ResponseInterface::class;
        }

        $returnTypeRaw = array_unique(array_filter($returnTypeRaw, is_string(...)));
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
                    ' * @return ' . implode('|', array_unique(array_filter($returnType, is_string(...)))),
                    ' */',
                ])),
            );
        }

        $createResponseMethod->addParam(
            $this->builderFactory->param('response')->setType('\\' . ResponseInterface::class),
        );

        if ($noHydrator === false) {
            $constructor->addParam(
                $this->builderFactory->param('responseSchemaValidator')->makePrivate()->setType('\\' . SchemaValidator::class),
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'responseSchemaValidator',
                    ),
                    new Node\Expr\Variable('responseSchemaValidator'),
                ),
            )->addParam(
                $this->builderFactory->param('hydrator')->makePrivate()->setType($hydrator->className->fullyQualified->source),
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
                $this->builderFactory->property('browser')->setType('\\' . Browser::class)->makeReadonly()->makePrivate(),
            );
            $constructor->addParam(
                $this->builderFactory->param('browser')->makePrivate()->setType('\\' . Browser::class),
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

        yield new File($package->destination->source, $operation->className->relative, $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }
}
