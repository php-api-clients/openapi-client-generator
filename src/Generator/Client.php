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
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Types;
use ApiClients\Tools\OpenApiClientGenerator\PrivatePromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Representation;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use Jawira\CaseConverter\Convert;
use NumberToWords\NumberToWords;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\UnionType;
use React\Http\Browser;
use ReflectionClass;
use ReflectionParameter;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_shift;
use function array_unique;
use function count;
use function explode;
use function implode;
use function strlen;
use function strpos;
use function trim;
use function ucfirst;

use const PHP_EOL;

final class Client
{
    /** @return iterable<File> */
    public static function generate(Configuration $configuration, string $pathPrefix, Representation\Client $client, Routers $routers): iterable
    {
        $operations = [];
        foreach ($client->paths as $path) {
            $operations = [...$operations, ...$path->operations];
        }

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim($configuration->namespace->source, '\\'));

        $class = $factory->class('Client')->implement(new Node\Name('ClientInterface'))->makeFinal();

        if ($configuration->entryPoints->call) {
            $class->addStmt(
                $factory->property('router')->setType('array')->setDefault([])->makePrivate(),
            );
        }

        if ($configuration->entryPoints->operations) {
            $class->addStmt(
                $factory->property('operations')->setType('OperationsInterface')->makeReadonly()->makePrivate(),
            );
        }

        if ($configuration->entryPoints->webHooks) {
            $class->addStmt(
                $factory->property('webHooks')->setType('WebHooks')->makeReadonly()->makePrivate(),
            );
        }

        $class->addStmt(
            $factory->property('routers')->setType('Routers')->makeReadonly()->makePrivate(),
        )->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new Param('authentication'))->setType('\\' . AuthenticationInterface::class),
            )->addParam(
                (new Param('browser'))->setType('\\' . Browser::class),
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

                return new Node\Expr\Assign(
                    new Node\Expr\Variable('browser'),
                    new Node\Expr\MethodCall(
                        $assignExpr,
                        'withFollowRedirects',
                        [
                            new Arg(
                                new Node\Expr\ConstFetch(new Node\Name('false')),
                            ),
                        ],
                    ),
                );
            })($client))->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('requestSchemaValidator'),
                    new Node\Expr\New_(
                        new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                        [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                                'VALIDATE_AS_REQUEST',
                            )),
                        ],
                    ),
                ),
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('responseSchemaValidator'),
                    new Node\Expr\New_(
                        new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                        [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                                'VALIDATE_AS_RESPONSE',
                            )),
                        ],
                    ),
                ),
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('hydrators'),
                    new Node\Expr\New_(
                        new Node\Name('Hydrators'),
                        [],
                    ),
                ),
            )->addStmts([
                ...($configuration->entryPoints->operations ? [
                    new Node\Expr\Assign(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'operations',
                        ),
                        new Node\Expr\New_(
                            new Node\Name('Operations'),
                            [
                                new Arg(
                                    new Node\Expr\New_(
                                        new Node\Name('Operators'),
                                        [
                                            new Arg(
                                                new Node\Expr\Variable('browser'),
                                                false,
                                                false,
                                                [],
                                                new Node\Identifier('browser'),
                                            ),
                                            new Arg(
                                                new Node\Expr\Variable('authentication'),
                                                false,
                                                false,
                                                [],
                                                new Node\Identifier('authentication'),
                                            ),
                                            new Arg(
                                                new Node\Expr\Variable('requestSchemaValidator'),
                                                false,
                                                false,
                                                [],
                                                new Node\Identifier('requestSchemaValidator'),
                                            ),
                                            new Arg(
                                                new Node\Expr\Variable('responseSchemaValidator'),
                                                false,
                                                false,
                                                [],
                                                new Node\Identifier('responseSchemaValidator'),
                                            ),
                                            new Arg(
                                                new Node\Expr\Variable('hydrators'),
                                                false,
                                                false,
                                                [],
                                                new Node\Identifier('hydrators'),
                                            ),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                    ),
                ] : []),
            ])->addStmts([
                ...($configuration->entryPoints->webHooks ? [
                    new Node\Expr\Assign(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'webHooks',
                        ),
                        new Node\Expr\New_(
                            new Node\Name('WebHooks'),
                            [
                                new Arg(
                                    new Node\Expr\Variable('requestSchemaValidator'),
                                    false,
                                    false,
                                    [],
                                    new Node\Identifier('requestSchemaValidator'),
                                ),
                                new Arg(
                                    new Node\Expr\Variable('hydrators'),
                                    false,
                                    false,
                                    [],
                                    new Node\Identifier('hydrator'),
                                ),
                            ],
                        ),
                    ),
                ] : []),
            ])->addStmt(
                new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'routers',
                        ),
                        new Node\Expr\New_(
                            new Node\Name('Routers'),
                            [
                                new Arg(
                                    new Node\Expr\Variable('browser'),
                                    false,
                                    false,
                                    [],
                                    new Node\Identifier('browser'),
                                ),
                                new Arg(
                                    new Node\Expr\Variable('authentication'),
                                    false,
                                    false,
                                    [],
                                    new Node\Identifier('authentication'),
                                ),
                                new Arg(
                                    new Node\Expr\Variable('requestSchemaValidator'),
                                    false,
                                    false,
                                    [],
                                    new Node\Identifier('requestSchemaValidator'),
                                ),
                                new Arg(
                                    new Node\Expr\Variable('responseSchemaValidator'),
                                    false,
                                    false,
                                    [],
                                    new Node\Identifier('responseSchemaValidator'),
                                ),
                                new Arg(
                                    new Node\Expr\Variable('hydrators'),
                                    false,
                                    false,
                                    [],
                                    new Node\Identifier('hydrators'),
                                ),
                            ],
                        ),
                    ),
                ),
            ),
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

                if (! array_key_exists($operation->matchMethod, $sortedOperations)) {
                    $sortedOperations[$operation->matchMethod] = [];
                }

                if (! array_key_exists($operationPathCount, $sortedOperations[$operation->matchMethod])) {
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
            $operationsIfs     = [];
            foreach ($sortedOperations as $method => $ops) {
                $opsTmts = [];
                foreach ($ops as $chunkCount => $moar) {
                    $returnTypes            = [];
                    $docBlockReturnTypes    = [];
                    $traverseForReturnTypes = static function (array $moar) use (&$returnTypes, &$docBlockReturnTypes, &$traverseForReturnTypes): void {
                        foreach (
                            array_map(
                                static fn (array $a): Representation\Operation => $a['operation'],
                                $moar['operations'],
                            ) as $operation
                        ) {
                            $returnTypes         = [
                                ...$returnTypes,
                                ...explode('|', Operation::getResultTypeFromOperation($operation)),
                            ];
                            $docBlockReturnTypes = [
                                ...$docBlockReturnTypes,
                                ...explode('|', Operation::getDocBlockResultTypeFromOperation($operation)),
                            ];
                        }

                        foreach ($moar['paths'] as $path) {
                            $traverseForReturnTypes($path);
                        }
                    };

                    $traverseForReturnTypes($moar);
                    $returnTypesUnfilterred = implode(
                        '|',
                        array_map(
                            'trim',
                            array_unique(
                                [...Types::filterDuplicatesAndIncompatibleRawTypes(...$returnTypes)],
                            ),
                        ),
                    );
                    $returnTypes            = implode(
                        '|',
                        array_map(
                            'trim',
                            array_filter(
                                array_unique(
                                    [...Types::filterDuplicatesAndIncompatibleRawTypes(...$returnTypes)],
                                ),
                                static fn (string $type): bool => $type !== 'void',
                            ),
                        ),
                    );
                    $docBlockReturnTypes    = implode(
                        '|',
                        array_map(
                            'trim',
                            array_filter(
                                array_unique(
                                    $docBlockReturnTypes,
                                ),
                                static fn (string $type): bool => $type !== 'void',
                            ),
                        ),
                    );
                    $chunkCountClasses[]    = $cc = new ChunkCount(
                        'Router\\' . (new Convert($method))->toPascal() . '\\' . (new Convert(NumberToWords::transformNumber('en', $chunkCount)))->toPascal(),
                        $returnTypes,
                        $docBlockReturnTypes,
                        self::traverseOperations(
                            $moar['operations'], /** @phpstan-ignore-line */
                            $moar['paths'], /** @phpstan-ignore-line */
                            0,
                            $routers,
                        ),
                    );

                    $returnOrExpression = $returnTypesUnfilterred === 'void' ? Node\Stmt\Expression::class : Node\Stmt\Return_::class;
                    $opsTmts[]          = [
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
                                                'class',
                                            )),
                                            new Arg(new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                'router',
                                            )),
                                        ],
                                    ),
                                    new Node\Expr\ConstFetch(new Node\Name('false')),
                                ),
                                [
                                    'stmts' => [
                                        new Node\Stmt\Expression(
                                            new Node\Expr\Assign(
                                                new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                                    new Node\Expr\Variable('this'),
                                                    'router',
                                                ), new Node\Expr\ClassConstFetch(
                                                    new Node\Name($cc->className),
                                                    'class',
                                                )),
                                                new Node\Expr\New_(
                                                    new Node\Name($cc->className),
                                                    [
                                                        new Arg(
                                                            new Node\Expr\PropertyFetch(
                                                                new Node\Expr\Variable('this'),
                                                                'routers',
                                                            ),
                                                            false,
                                                            false,
                                                            [],
                                                            new Node\Identifier('routers'),
                                                        ),
                                                    ],
                                                ),
                                            ),
                                        ),
                                    ],
                                ],
                            ),
                            new $returnOrExpression(
                                new Expr\MethodCall(
                                    new Node\Expr\ArrayDimFetch(new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        'router',
                                    ), new Node\Expr\ClassConstFetch(
                                        new Node\Name($cc->className),
                                        'class',
                                    )),
                                    'call',
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
                        $first   = array_shift($opsTmts);
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
            $operationsIf       = new Node\Stmt\If_(
                $firstOperationsIfs[0], /** @phpstan-ignore-line */
                [
                    'stmts' => $firstOperationsIfs[1], /** @phpstan-ignore-line */
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
                $factory->method('call')->makePublic()->setDocComment(
                    new Doc(implode(PHP_EOL, [
                        '// phpcs:disable',
                        '/**',
                        ' * @return ' . (static function (array $operations): string {
                            $count    = count($operations);
                            $lastItem = $count - 1;
                            $left     = '';
                            $right    = '';
                            for ($i = 0; $i < $count; $i++) {
                                $returnType = Operation::getDocBlockResultTypeFromOperation($operations[$i]);
                                if ($i !== $lastItem) {
                                    $left .= '($call is Operation\\' . $operations[$i]->classNameSanitized->relative . '::OPERATION_MATCH ? ' . $returnType . ' : ';
                                } else {
                                    $left .= $returnType;
                                }

                                $right .= ')';
                            }

                            return $left . $right;
                        })($operations),
                        ' */',
                        '// phpcs:enable',
                    ])),
                )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))->setReturnType(
                    new UnionType(
                        array_map(
                            static fn (string $type): Name => new Name($type),
                            array_unique(
                                [
                                    ...Types::filterDuplicatesAndIncompatibleRawTypes(...(static function (array $operations): iterable {
                                        foreach ($operations as $operation) {
                                            yield from explode('|', Operation::getResultTypeFromOperation($operation));
                                        }
                                    })($operations)),
                                ],
                            ),
                        ),
                    ),
                )->addStmt(
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
                        ),
                    ),
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
                        ),
                    ),
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
                        ),
                    ),
                )->addStmt($operationsIf)->addStmt(
                    new Node\Stmt\Throw_(
                        new Node\Expr\New_(
                            new Node\Name('\InvalidArgumentException'),
                        ),
                    ),
                ),
            );
        }

        if ($configuration->entryPoints->operations) {
            $class->addStmt(
                $factory->method('operations')->makePublic()->setReturnType('OperationsInterface')->addStmt(new Node\Stmt\Return_(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'operations',
                    ),
                )),
            );
        }

        if ($configuration->entryPoints->webHooks) {
            $class->addStmt(
                $factory->method('webHooks')->makePublic()->setReturnType('\\' . WebHooksInterface::class)->addStmt(new Node\Stmt\Return_(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'webHooks',
                    ),
                )),
            );
        }

        yield new File($pathPrefix, 'Client', $stmt->addStmt($class)->getNode());

        foreach ($routers->get() as $router) {
            yield from self::createRouter(
                $pathPrefix,
                $configuration->namespace->source . '\\',
                $router,
                $routers,
            );
        }

        /** @phpstan-ignore-next-line */
        if (! isset($chunkCountClasses)) {
            return;
        }

        foreach ($chunkCountClasses as $chunkCountClass) {
            yield from self::createRouterChunkSize(
                $pathPrefix,
                $configuration->namespace->source . '\\',
                $chunkCountClass,
            );
        }

        yield from \ApiClients\Tools\OpenApiClientGenerator\Generator\Routers::generate($configuration, $pathPrefix, $routers);
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
            $operations['operations'][] = [ /** @phpstan-ignore-line */
                'operation' => $operation,
                'path' => $path,
            ];

            return $operations;
        }

        $chunk = array_shift($operationPath);
        if (! array_key_exists($chunk, $operations['paths'])) { /** @phpstan-ignore-line */
            $operations['paths'][$chunk] = [ /** @phpstan-ignore-line */
                'operations' => [],
                'paths' => [],
            ];
        }

        $operations['paths'][$chunk] = self::traverseOperationPaths($operations['paths'][$chunk], $operationPath, $operation, $path); /** @phpstan-ignore-line */

        return $operations;
    }

    /**
     * @param array<Representation\Path> $paths
     *
     * @return iterable<Representation\Path>
     */
    private static function operationsInThisThree(array $paths, int $level, Routers $routers): iterable
    {
        foreach ($paths as $path) {
            yield from $path['operations'];
            yield from self::operationsInThisThree(
                $path['paths'], /** @phpstan-ignore-line */
                $level + 1,
                $routers,
            );
        }
    }

    /**
     * @param array<Representation\Operation> $operations
     * @param array<Representation\Path>      $paths
     *
     * @return array<Node\Stmt>
     */
    private static function traverseOperations(array $operations, array $paths, int $level, Routers $routers): array
    {
        $nonArgumentPathChunks = [];
        foreach (array_keys($paths) as $pathChunk) {
            if (strpos($pathChunk, '{') === 0) {
                continue;
            }

            $nonArgumentPathChunks[] = new Node\Expr\ArrayItem(new Node\Scalar\String_($pathChunk));
        }

        $opsInTree = [
            ...self::operationsInThisThree(
                $paths,
                $level,
                $routers,
            ),
        ];

        $ifs = [];

//        if (count($opsIntree) === 13) {
//            $operations = [...$operations, ...$opsIntree];
//        }

        foreach ($operations as $operation) {
            $ifs[] = [
                new Node\Expr\BinaryOp\Equal(
                    new Node\Expr\Variable('call'),
                    new Node\Scalar\String_($operation['operation']->matchMethod . ' ' . $operation['operation']->path), /** @phpstan-ignore-line */
                ),
                static::callOperation(
                    $routers,
                    ...$operation, /** @phpstan-ignore-line */
                ),
            ];
        }

//        if (count($opsIntree) > 13) {
        foreach ($paths as $pathChunk => $path) {
            $ifs[] = [
                new Node\Expr\BinaryOp\Equal(
                    new Node\Expr\ArrayDimFetch(
                        new Node\Expr\Variable('pathChunks'),
                        new Node\Scalar\LNumber($level),
                    ),
                    new Node\Scalar\String_($pathChunk),
                ),
                self::traverseOperations(
                    $path['operations'], /** @phpstan-ignore-line */
                    $path['paths'], /** @phpstan-ignore-line */
                    $level + 1,
                    $routers,
                ),
            ];
        }

//        }

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

    /** @return array<Node\Stmt> */
    private static function callOperation(Routers $routers, Representation\Operation $operation, Representation\Path $path): array
    {
        $returnType = implode(
            '|',
            [
                ...Types::filterDuplicatesAndIncompatibleRawTypes(
                    ...explode(
                        '|',
                        Operation::getResultTypeFromOperation($operation),
                    ),
                ),
            ],
        );
        $router     =  $routers->add(
            $operation->matchMethod,
            $operation->group,
            $operation->name,
            $returnType,
            Operation::getDocBlockResultTypeFromOperation($operation),
            [
                ...(count($operation->parameters) > 0 ? [
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
                                            'false',
                                        ),
                                    ),
                                ),
                                [
                                    'stmts' => [
                                        new Node\Stmt\Throw_(
                                            new Node\Expr\New_(
                                                new Node\Name('\InvalidArgumentException'),
                                                [
                                                    new Arg(
                                                        new Node\Scalar\String_('Missing mandatory field: ' . $param->targetName),
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
                ] : []),
                ...($operation->matchMethod !== 'LIST' ? self::makeCall(
                    $operation,
                    $path,
                    $returnType === 'void' ? static fn (Expr $expr): Node\Stmt\Expression => new Node\Stmt\Expression($expr) : static fn (Expr $expr): Node\Stmt\Return_ => new Node\Stmt\Return_($expr)
                ) : [
                    new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Expr\ArrayDimFetch(
                                new Expr\Variable('arguments'),
                                new Node\Scalar\String_($operation->metaData['listOperation']['key']), /** @phpstan-ignore-line */
                            ),
                            new Node\Scalar\LNumber($operation->metaData['listOperation']['initialValue']), /** @phpstan-ignore-line */
                        ),
                    ),
                    new Node\Stmt\Do_(
                        new Node\Expr\BinaryOp\Greater(
                            new Expr\FuncCall(
                                new Name('count'),
                                [
                                    new Arg(new Node\Expr\Variable('items')),
                                ],
                            ),
                            new Node\Scalar\LNumber(0),
                        ),
                        [
                            ...self::makeCall(
                                $operation,
                                $path,
                                static fn (Expr $expr): Node\Stmt\Expression => new Node\Stmt\Expression(
                                    new Node\Expr\Assign(
                                        new Expr\Variable('items'),
                                        new Expr\Array_([
                                            new Expr\ArrayItem(value: $expr, unpack: true),
                                        ], [
                                            'kind' => Node\Expr\Array_::KIND_SHORT,
                                        ]),
                                    ),
                                ),
                            ),
                            new Node\Stmt\Expression(
                                new Expr\YieldFrom(
                                    new Expr\Variable('items'),
                                ),
                            ),
                            new Node\Stmt\Expression(
                                new Expr\PostInc(
                                    new Expr\ArrayDimFetch(
                                        new Expr\Variable('arguments'),
                                        new Node\Scalar\String_($operation->metaData['listOperation']['key']), /** @phpstan-ignore-line */
                                    ),
                                ),
                            ),
                        ],
                    ),
                ]),
            ],
        );

        $returnOrExpression = $returnType === 'void' ? Node\Stmt\Expression::class : Node\Stmt\Return_::class;

        return [
            new $returnOrExpression(
                new Expr\MethodCall(
                    new Expr\MethodCall(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'routers',
                        ),
                        $router->loopUpMethod,
                    ),
                    (new Convert(Utils::fixKeyword($router->method)))->toCamel(),
                    [
                        new Arg(
                            new Node\Expr\Variable(
                                'params',
                            ),
                        ),
                    ],
                ),
            ),
        ];
    }

    /** @return array<Node\Stmt> */
    private static function makeCall(Representation\Operation $operation, Representation\Path $path, callable $calWrap): array
    {
        return [
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\Variable('operator'),
                new Node\Expr\New_(
                    new Node\Name($operation->operatorClassName->relative),
                    [
                        new Arg(new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'browser',
                        )),
                        new Arg(new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'authentication',
                        )),
                        ...(count($operation->requestBody) > 0 ? [
                            new Arg(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'requestSchemaValidator',
                            )),
                        ] : []),
                        /** @phpstan-ignore-next-line */
                        ...(count(array_filter((new ReflectionClass($operation->className->fullyQualified->source))->getConstructor()->getParameters(), static fn (ReflectionParameter $parameter): bool => $parameter->name === 'responseSchemaValidator' || $parameter->name === 'hydrator')) > 0 ? [
                            new Arg(new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'responseSchemaValidator',
                            )),
                            new Arg(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        'hydrators',
                                    ),
                                    'getObjectMapper' . ucfirst($path->hydrator->methodName),
                                ),
                            ),
                        ] : []),
                    ],
                ),
            )),
            $calWrap(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable('operator'),
                    'call',
                    [
                        ...(static function (array $params): iterable {
                            foreach ($params as $param) {
                                yield new Arg(new Node\Expr\ArrayDimFetch(new Node\Expr\Variable('arguments'), new Node\Scalar\String_($param->targetName)));
                            }
                        })($operation->parameters),
                        ...(count($operation->requestBody) > 0 ? [new Arg(new Node\Expr\Variable('params'))] : []),
                    ],
                ),
            ),
        ];
    }

    /** @return iterable<File> */
    private static function createRouter(string $pathPrefix, string $namespace, RouterClass $router, Routers $routers): iterable
    {
        $className = $routers->createClassName(Utils::fixKeyword($router->method), $router->group, '')->class;
        $factory   = new BuilderFactory();
        $stmt      = $factory->namespace(Utils::dirname($namespace . $className));
        $class     = $factory->class(Utils::basename($namespace . $className))->makeFinal()->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new PrivatePromotedPropertyAsParam('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator'),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('hydrators'))->setType('\\' . $namespace . 'Hydrators'),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('browser'))->setType('\\' . Browser::class),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('authentication'))->setType('\\' . AuthenticationInterface::class),
            ),
        );

        foreach ($router->methods as $method) {
            $class->addStmt(
                $factory->method(
                    (new Convert($method->name))->toCamel(),
                )->makePublic()->addParam(
                    (new Param('params'))->setType('array'),
                )->addStmts($method->nodes)->setReturnType(
                    $method->returnType,
                )->setDocComment(
                    new Doc(
                        implode(
                            PHP_EOL,
                            [
                                '/**',
                                ' * @return ' . $method->docBlockReturnType,
                                ' */',
                            ],
                        ),
                    ),
                ),
            );
        }

        yield new File($pathPrefix, $className, $stmt->addStmt($class)->getNode());
    }

    /** @return iterable<File> */
    private static function createRouterChunkSize(string $pathPrefix, string $namespace, ChunkCount $chunkCount): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(Utils::dirname($namespace . $chunkCount->className));

        $class = $factory->class(Utils::basename($namespace . $chunkCount->className))->makeFinal()->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new PrivatePromotedPropertyAsParam('routers'))->setType('\\' . $namespace . 'Routers'),
            ),
        );

        $callMethod = $factory->method('call')->makePublic()->addParams([
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
                    new Node\Name('\InvalidArgumentException'),
                ),
            ),
        );

        if (strlen($chunkCount->returnType) > 0) {
            $callMethod->setReturnType($chunkCount->returnType);
        }

        if (strlen($chunkCount->docBlockReturnType) > 0) {
            $callMethod->setDocComment(
                new Doc(
                    implode(
                        PHP_EOL,
                        [
                            '/**',
                            ' * @return ' . $chunkCount->docBlockReturnType,
                            ' */',
                        ],
                    ),
                ),
            );
        }

        $class->addStmt($callMethod);

        yield new File($pathPrefix, $chunkCount->className, $stmt->addStmt($class)->getNode());
    }
}
