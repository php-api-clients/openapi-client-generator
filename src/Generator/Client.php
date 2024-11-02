<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Methods\ChunkCount;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\PHPStan\ClientCallReturnTypes;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\PHPStan\ClientCallReturnTypesTest;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers\RouterClass;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\ConfigurationPackage;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Types;
use Jawira\CaseConverter\Convert;
use League\OpenAPIValidation\Schema\SchemaValidator;
use NumberToWords\NumberToWords;
use OpenAPITools\Configuration\Package as ConfigurationPackageType;
use OpenAPITools\Configuration\Package\QA\Tool;
use OpenAPITools\Contract\FileGenerator;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\File;
use OpenAPITools\Utils\Utils;
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
use function dirname;
use function explode;
use function implode;
use function str_starts_with;
use function trim;
use function ucfirst;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * @phpstan-type OperationTreeEntry array{operation: Namespaced\Operation, path: Namespaced\Path}
 * @phpstan-type OperationTree array{operations: list<OperationTreeEntry>, paths: array<string, mixed>}
 */
final readonly class Client implements FileGenerator
{
    public function __construct(
        private BuilderFactory $builderFactory,
        private bool $call,
        private bool $operations,
    ) {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Namespaced\Representation $representation): iterable
    {
        $package = ConfigurationPackage::unwrap($package);

        $routers    = new Routers();
        $operations = [];
        foreach ($representation->client->paths as $path) {
            $operations = [...$operations, ...$path->operations];
        }

        $stmt = $this->builderFactory->namespace(trim($package->namespace->source, '\\'));

        $class = $this->builderFactory->class('Client')->implement(new Node\Name('ClientInterface'))->makeFinal();

        if ($this->call) {
            $class->addStmt(
                $this->builderFactory->property('router')->setType('array')->setDefault([])->makePrivate(),
            );
        }

        if ($this->operations) {
            $class->addStmt(
                $this->builderFactory->property('operations')->setType('OperationsInterface')->makeReadonly()->makePrivate(),
            );
        }

        $class->addStmt(
            $this->builderFactory->property('routers')->setType('\\' . $package->namespace->source . '\\Internal\\Routers')->makeReadonly()->makePrivate(),
        )->addStmt(
            $this->builderFactory->method('__construct')->makePublic()->addParam(
                $this->builderFactory->param('authentication')->makePrivate()->makeReadonly()->setType('\\' . AuthenticationInterface::class),
            )->addParam(
                $this->builderFactory->param('browser')->makePrivate()->setType('\\' . Browser::class),
            )->addStmt((static function (Namespaced\Client $client): Node\Stmt\Expression {
                $browserVariable = new Node\Expr\Variable('browser');
                $browserWithBase = $client->baseUrl === null
                    ? $browserVariable
                    : new Node\Expr\MethodCall(
                        $browserVariable,
                        'withBase',
                        [
                            new Arg(
                                new Node\Scalar\String_($client->baseUrl),
                            ),
                        ],
                    );

                return new Node\Stmt\Expression(new Node\Expr\Assign(
                    new Node\Expr\Variable('browser'),
                    new Node\Expr\MethodCall(
                        $browserWithBase,
                        'withFollowRedirects',
                        [
                            new Arg(
                                new Node\Expr\ConstFetch(new Node\Name('false')),
                            ),
                        ],
                    ),
                ));
            })($representation->client))->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('requestSchemaValidator'),
                    new Node\Expr\New_(
                        new Node\Name('\\' . SchemaValidator::class),
                        [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\\' . SchemaValidator::class),
                                'VALIDATE_AS_REQUEST',
                            )),
                        ],
                    ),
                ),
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('responseSchemaValidator'),
                    new Node\Expr\New_(
                        new Node\Name('\\' . SchemaValidator::class),
                        [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\\' . SchemaValidator::class),
                                'VALIDATE_AS_RESPONSE',
                            )),
                        ],
                    ),
                ),
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('hydrators'),
                    new Node\Expr\New_(
                        new Node\Name('\\' . $package->namespace->source . '\\Internal\\Hydrators'),
                        [],
                    ),
                ),
            )->addStmts([
                ...($this->operations ? [
                    new Node\Stmt\Expression(new Node\Expr\Assign(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'operations',
                        ),
                        new Node\Expr\New_(
                            new Node\Name('Operations'),
                            [
                                new Arg(
                                    new Node\Expr\New_(
                                        new Node\Name('\\' . $package->namespace->source . '\\Internal\\Operators'),
                                        [
                                            new Arg(
                                                new Node\Expr\Variable('authentication'),
                                                false,
                                                false,
                                                [],
                                                new Node\Identifier('authentication'),
                                            ),
                                            new Arg(
                                                new Node\Expr\Variable('browser'),
                                                false,
                                                false,
                                                [],
                                                new Node\Identifier('browser'),
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
                    )),
                ] : []),
            ])->addStmt(
                new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'routers',
                        ),
                        new Node\Expr\New_(
                            new Node\Name('\\' . $package->namespace->source . '\\Internal\\Routers'),
                            [
                                new Arg(
                                    new Node\Expr\Variable('authentication'),
                                    false,
                                    false,
                                    [],
                                    new Node\Identifier('authentication'),
                                ),
                                new Arg(
                                    new Node\Expr\Variable('browser'),
                                    false,
                                    false,
                                    [],
                                    new Node\Identifier('browser'),
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
            )->addStmts([
                ...($representation->webHooks === [] ? [] : [
                    new Node\Stmt\Expression(new Node\Expr\Assign(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'webHooks',
                        ),
                        new Node\Expr\New_(
                            new Node\Name('\\' . $package->namespace->source . '\\WebHooks'),
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
                    )),
                ]),
            ]),
        );

        if ($representation->webHooks !== []) {
            $class->addStmt(
                $this->builderFactory->property('webHooks')->setType('\\' . $package->namespace->source . '\\WebHooks')->makeReadonly()->makePrivate(),
            )->addStmt(
                $this->builderFactory->method('webHooks')->makePublic()->setReturnType('\\' . WebHooksInterface::class)->addStmt(
                    new Node\Stmt\Return_(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'webHooks',
                        ),
                    ),
                ),
            );
        }

        /** @var array<string, array<int, OperationTree>> $sortedOperations */
        $sortedOperations = [];
        foreach ($representation->client->paths as $path) {
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

                /** @var OperationTree $operationTree */
                $operationTree                                                  = $sortedOperations[$operation->matchMethod][$operationPathCount];
                $sortedOperations[$operation->matchMethod][$operationPathCount] = self::traverseOperationPaths($operationTree, $operationPath, $operation, $path);
            }
        }

        $chunkCountClasses = [];
        if ($this->call) {
            $operationsIfs = [];
            foreach ($sortedOperations as $method => $ops) {
                $opsTmts = [];
                foreach ($ops as $chunkCount => $operationTree) {
                    /** @var OperationTree $operationTree */
                    $returnTypesList         = [];
                    $docBlockReturnTypesList = [];
                    $traverseForReturnTypes  = static function (array $tree) use (&$returnTypesList, &$docBlockReturnTypesList, &$traverseForReturnTypes): void {
                        /** @var list<OperationTreeEntry> $operationEntries */
                        $operationEntries = $tree['operations'];
                        foreach ($operationEntries as $operationEntry) {
                            $operation               = $operationEntry['operation'];
                            $returnTypesList         = [
                                ...$returnTypesList,
                                ...explode('|', Operation::getResultTypeFromOperation($operation)),
                            ];
                            $docBlockReturnTypesList = [
                                ...$docBlockReturnTypesList,
                                ...explode('|', Operation::getDocBlockResultTypeFromOperation($operation)),
                            ];
                        }

                        /** @var array<string, mixed> $nestedPaths */
                        $nestedPaths = $tree['paths'];
                        foreach ($nestedPaths as $nestedTree) {
                            /** @var OperationTree $nestedTree */
                            $traverseForReturnTypes($nestedTree);
                        }
                    };

                    $traverseForReturnTypes($operationTree);
                    $returnTypesUnfilterred = implode(
                        '|',
                        array_map(
                            trim(...),
                            array_unique(
                                [...Types::filterDuplicatesAndIncompatibleRawTypes(...$returnTypesList)],
                            ),
                        ),
                    );
                    $returnTypes            = implode(
                        '|',
                        array_map(
                            trim(...),
                            array_filter(
                                array_unique(
                                    [...Types::filterDuplicatesAndIncompatibleRawTypes(...$returnTypesList)],
                                ),
                                static fn (string $type): bool => $type !== 'void',
                            ),
                        ),
                    );
                    $docBlockReturnTypes    = implode(
                        '|',
                        array_map(
                            trim(...),
                            array_filter(
                                array_unique(
                                    $docBlockReturnTypesList,
                                ),
                                static fn (string $type): bool => $type !== 'void',
                            ),
                        ),
                    );
                    $chunkCountClasses[]    = $cc = new ChunkCount(
                        'Internal\\Router\\' . new Convert($method)->toPascal() . '\\' . new Convert(NumberToWords::transformNumber('en', $chunkCount))->toPascal(),
                        $returnTypes,
                        $docBlockReturnTypes,
                        self::traverseOperations(
                            $package,
                            $operationTree['operations'],
                            $operationTree['paths'],
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
                                new Node\Expr\BinaryOp\Identical(
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
                        $first = array_shift($opsTmts);
                        if ($first === null) {
                            return [];
                        }

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

            if ($operationsIfs === []) {
                return;
            }

            $firstOperationsIf = array_shift($operationsIfs);

            $operationsIf = new Node\Stmt\If_(
                $firstOperationsIf[0],
                [
                    'stmts' => $firstOperationsIf[1],
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
                $this->builderFactory->method('call')->makePublic()->setDocComment(
                    new Doc(implode(PHP_EOL, [
                        ...($package->qa->phpcs instanceof Tool && $package->qa->phpcs->enabled ? ['// phpcs:disable'] : []),
                        '/**',
                    //                        ' * @return ' . (static function (array $operations): string {
                    //                            $count    = count($operations);
                    //                            $lastItem = $count - 1;
                    //                            $left     = '';
                    //                            $right    = '';
                    //                            for ($i = 0; $i < $count; $i++) {
                    //                                $returnType = Operation::getDocBlockResultTypeFromOperation($operations[$i]);
                    //                                if ($i !== $lastItem) {
                    //                                    $left .= '($call is "' . $operations[$i]->matchMethod . ' ' . $operations[$i]->path . '" ? ' . $returnType . ' : ';
                    //                                } else {
                    //                                    $left .= $returnType;
                    //                                }
                    //
                    //                                $right .= ')';
                    //                            }
                    //
                    //                            return $left . $right;
                    //                        })($operations),
                        ' */',
                        ...($package->qa->phpcs instanceof Tool && $package->qa->phpcs->enabled ? ['// phpcs:enable'] : []),
                    ])),
                )->addParam($this->builderFactory->param('call')->setType('string'))->addParam($this->builderFactory->param('params')->setType('array')->setDefault([]))->setReturnType(
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
                    new Node\Stmt\Expression(
                        new Node\Expr\Throw_(
                            new Node\Expr\New_(
                                new Node\Name('\InvalidArgumentException'),
                            ),
                        ),
                    ),
                ),
            );
        }

        if ($this->operations) {
            $class->addStmt(
                $this->builderFactory->method('operations')->makePublic()->setReturnType('OperationsInterface')->addStmt(new Node\Stmt\Return_(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'operations',
                    ),
                )),
            );
        }

        yield new File($package->destination->source, 'Client', $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);

        foreach ($routers->get() as $router) {
            yield from $this->createRouter(
                $package,
                $router,
                $routers,
            );
        }

        yield from \ApiClients\Tools\OpenApiClientGenerator\Generator\Routers::generate($package, $routers);

        foreach ($chunkCountClasses as $chunkCountClass) {
            yield from $this->createRouterChunkSize(
                $package,
                $chunkCountClass,
            );
        }

        if (! $package->qa->phpstan instanceof Tool || ! $package->qa->phpstan->enabled) {
            return;
        }

        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'phpstan-assertType-mock.php';

        yield from ClientCallReturnTypes::generate($package, $representation->client);
        yield from ClientCallReturnTypesTest::generate($package, $representation->client);

        if ($package->qa->phpstan->configFilePath === null) {
            return;
        }

        yield new File($package->destination->source, '../' . $package->qa->phpstan->configFilePath, implode(PHP_EOL, [
            'services:',
            '  - class: ' . $package->namespace->source . '\PHPStan\ClientCallReturnTypes',
            '    tags:',
            '      - phpstan.broker.dynamicMethodReturnTypeExtension',
            '',
        ]), File::DO_NOT_LOAD_ON_WRITE);
    }

    /**
     * @param OperationTree $operations
     * @param list<string>  $operationPath
     *
     * @return OperationTree
     */
    private static function traverseOperationPaths(array $operations, array $operationPath, Namespaced\Operation $operation, Namespaced\Path $path): array
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

        /** @var OperationTree $childTree */
        $childTree                   = $operations['paths'][$chunk];
        $operations['paths'][$chunk] = self::traverseOperationPaths($childTree, $operationPath, $operation, $path);

        return $operations;
    }

    /**
     * @param array<string, mixed> $paths
     *
     * @return iterable<OperationTreeEntry>
     */
    private static function operationsInThisThree(array $paths, int $level, Routers $routers): iterable
    {
        foreach ($paths as $path) {
            /** @var OperationTree $path */
            yield from $path['operations'];
            yield from self::operationsInThisThree(
                $path['paths'],
                $level + 1,
                $routers,
            );
        }
    }

    /**
     * @param list<OperationTreeEntry> $operations
     * @param array<string, mixed>     $paths
     *
     * @return list<Node\Stmt>
     */
    private static function traverseOperations(ConfigurationPackageType $package, array $operations, array $paths, int $level, Routers $routers): array
    {
        $nonArgumentPathChunks = [];
        foreach (array_keys($paths) as $pathChunk) {
            if (str_starts_with($pathChunk, '{')) {
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
                    new Node\Scalar\String_($operation['operation']->matchMethod . ' ' . $operation['operation']->path),
                ),
                self::callOperation(
                    $package,
                    $routers,
                    ...$operation,
                ),
            ];
        }

//        if (count($opsIntree) > 13) {
        foreach ($paths as $pathChunk => $pathTreeValue) {
            /** @var OperationTree $pathTree */
            $pathTree = $pathTreeValue;
            $ifs[]    = [
                new Node\Expr\BinaryOp\Equal(
                    new Node\Expr\ArrayDimFetch(
                        new Node\Expr\Variable('pathChunks'),
                        new Node\Scalar\LNumber($level),
                    ),
                    new Node\Scalar\String_($pathChunk),
                ),
                self::traverseOperations(
                    $package,
                    $pathTree['operations'],
                    $pathTree['paths'],
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

    /** @return list<Node\Stmt> */
    private static function callOperation(ConfigurationPackageType $package, Routers $routers, Namespaced\Operation $operation, Namespaced\Path $path): array
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

        $listOperationKey          = '';
        $listOperationInitialValue = 0;
        if ($operation->matchMethod === 'LIST') {
            /** @var array{key: string, initialValue: int} $listOperationMeta */
            $listOperationMeta         = $operation->metaData['listOperation'];
            $listOperationKey          = $listOperationMeta['key'];
            $listOperationInitialValue = $listOperationMeta['initialValue'];
        }

        $router =  $routers->add(
            $package,
            $operation->matchMethod,
            $operation->group ?? '',
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
                                        new Node\Stmt\Expression(
                                            new Node\Expr\Throw_(
                                                new Node\Expr\New_(
                                                    new Node\Name('\InvalidArgumentException'),
                                                    [
                                                        new Arg(
                                                            new Node\Scalar\String_('Missing mandatory field: ' . $param->targetName),
                                                        ),
                                                    ],
                                                ),
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
                    $returnType === 'void' ? static fn (Expr $expr): Node\Stmt\Expression => new Node\Stmt\Expression($expr) : static fn (Expr $expr): Node\Stmt\Return_ => new Node\Stmt\Return_($expr),
                ) : [
                    new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Expr\ArrayDimFetch(
                                new Expr\Variable('arguments'),
                                new Node\Scalar\String_($listOperationKey),
                            ),
                            new Node\Scalar\LNumber($listOperationInitialValue),
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
                                        new Node\Scalar\String_($listOperationKey),
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
                    new Convert(Utils::fixKeyword($router->method))->toCamel(),
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

    /** @return list<Node\Stmt> */
    private static function makeCall(Namespaced\Operation $operation, Namespaced\Path $path, callable $calWrap): array
    {
        /** @var class-string $className */
        $className                 = $operation->className->fullyQualified->source;
        $constructor               = new ReflectionClass($className)->getConstructor();
        $needsHydratorAndValidator = $constructor !== null && count(array_filter($constructor->getParameters(), static fn (ReflectionParameter $parameter): bool => $parameter->name === 'responseSchemaValidator' || $parameter->name === 'hydrator')) > 0;

        return [
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\Variable('operator'),
                new Node\Expr\New_(
                    new Node\Name($operation->operatorClassName->fullyQualified->source),
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
                        ...($needsHydratorAndValidator ? [
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
    private function createRouter(ConfigurationPackageType $package, RouterClass $router, Routers $routers): iterable
    {
        $className = $routers->createClassName($package, Utils::fixKeyword($router->method), $router->group, '')->class;
        $stmt      = $this->builderFactory->namespace($className->namespace->source);
        $class     = $this->builderFactory->class($className->className)->makeFinal()->addStmt(
            $this->builderFactory->method('__construct')->makePublic()->addParam(
                $this->builderFactory->param('requestSchemaValidator')->makePrivate()->setType('\\' . SchemaValidator::class),
            )->addParam(
                $this->builderFactory->param('responseSchemaValidator')->makePrivate()->setType('\\' . SchemaValidator::class),
            )->addParam(
                $this->builderFactory->param('hydrators')->makePrivate()->setType('\\' . $package->namespace->source . '\\Internal\\Hydrators'),
            )->addParam(
                $this->builderFactory->param('browser')->makePrivate()->setType('\\' . Browser::class),
            )->addParam(
                $this->builderFactory->param('authentication')->makePrivate()->setType('\\' . AuthenticationInterface::class),
            ),
        );

        foreach ($router->methods as $method) {
            $class->addStmt(
                $this->builderFactory->method(
                    new Convert($method->name)->toCamel(),
                )->makePublic()->addParam(
                    $this->builderFactory->param('params')->setType('array'),
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

        yield new File($package->destination->source, $className->relative, $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }

    /** @return iterable<File> */
    private function createRouterChunkSize(ConfigurationPackageType $package, ChunkCount $chunkCount): iterable
    {
        $namespace = $package->namespace->source . '\\';
        $stmt      = $this->builderFactory->namespace(Utils::dirname($namespace . $chunkCount->className));

        $class = $this->builderFactory->class(Utils::basename($namespace . $chunkCount->className))->makeFinal()->addStmt(
            $this->builderFactory->method('__construct')->makePublic()->addParam(
                $this->builderFactory->param('routers')->makePrivate()->setType('\\' . $namespace . 'Internal\\Routers'),
            ),
        );

        $callMethod = $this->builderFactory->method('call')->makePublic()->addParams([
            ...(function (array $params): iterable {
                foreach ($params as $param => $type) {
                    yield $this->builderFactory->param($param)->setType($type);
                }
            })([
                'call' => 'string',
                'params' => 'array',
                'pathChunks' => 'array',
            ]),
        ])->addStmts($chunkCount->nodes)->addStmt(
            new Node\Stmt\Expression(
                new Node\Expr\Throw_(
                    new Node\Expr\New_(
                        new Node\Name('\InvalidArgumentException'),
                    ),
                ),
            ),
        );

        if ($chunkCount->returnType !== '') {
            $callMethod->setReturnType($chunkCount->returnType);
        }

        if ($chunkCount->docBlockReturnType !== '') {
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

        yield new File($package->destination->source, $chunkCount->className, $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }
}
