<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use Jawira\CaseConverter\Convert;
use League\OpenAPIValidation\Schema\SchemaValidator;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use RuntimeException;
use Throwable;

use function array_unique;
use function implode;
use function lcfirst;
use function trim;
use function ucfirst;

use const PHP_EOL;

final class WebHooks
{
    /**
     * @param array<string, Hydrator>             $webHooksHydrators
     * @param array<class-string, array<WebHook>> $webHooks
     *
     * @return iterable<File>
     */
    public static function generate(string $pathPrefix, string $namespace, array $webHooksHydrators, array $webHooks): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim($namespace, '\\'));

        $class = $factory->class('WebHooks')->makeFinal()->implement('\\' . WebHooksInterface::class);
        $class->addStmt($factory->property('requestSchemaValidator')->setType('\\' . SchemaValidator::class)->makeReadonly()->makePrivate());
        $class->addStmt($factory->property('hydrator')->setType('Hydrators')->makeReadonly()->makePrivate());

        $constructor = $factory->method('__construct')->makePublic()->addParams([
            (new Param('requestSchemaValidator'))->setType('\\' . SchemaValidator::class),
            (new Param('hydrator'))->setType('Hydrators'),
        ])->addStmts([
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'requestSchemaValidator',
                ),
                new Node\Expr\Variable('requestSchemaValidator'),
            ),
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'hydrator',
                ),
                new Node\Expr\Variable('hydrator'),
            ),
        ]);
        $class->addStmt($constructor);

        $class->addStmt(
            $factory->method('hydrateWebHook')->makePublic()->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @template H',
                    ' * @param class-string<H> $className',
                    ' * @return H',
                    ' */',
                ])),
            )->setReturnType('object')->addParam(
                (new Param('className'))->setType('string'),
            )->addParam(
                (new Param('data'))->setType('array'),
            )->addStmt(new Node\Stmt\Return_(
                new Node\Expr\MethodCall(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'hydrator',
                    ),
                    'hydrateObject',
                    [
                        new Node\Arg(new Node\Expr\Variable('className')),
                        new Node\Arg(new Node\Expr\Variable('data')),
                    ],
                ),
            )),
        );

        $class->addStmt(
            $factory->method('serializeWebHook')->makePublic()->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @return array{className: class-string, data: mixed}',
                    ' */',
                ])),
            )->setReturnType('array')->addParam(
                (new Param('object'))->setType('object'),
            )->addStmt(new Node\Stmt\Return_(
                new Node\Expr\Array_([
                    new Node\Expr\ArrayItem(
                        new Node\Expr\ClassConstFetch(
                            new Node\Expr\Variable('object'),
                            'class',
                        ),
                        new Node\Scalar\String_('className'),
                    ),
                    new Node\Expr\ArrayItem(
                        new Node\Expr\MethodCall(
                            new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'hydrator',
                            ),
                            'serializeObject',
                            [
                                new Node\Arg(new Node\Expr\Variable('object')),
                            ],
                        ),
                        new Node\Scalar\String_('data'),
                    ),
                ]),
            )),
        );

        $method     = $factory->method('resolve')->makePublic()->setReturnType('object')->setDocComment(new Doc(implode(PHP_EOL, [
            '/**',
            ' * @return ' . implode('|', array_unique(
                (static function (WebHook ...$webHooks): array {
                    $schemas = [];
                    foreach ($webHooks as $webHook) {
                        foreach ($webHook->schema as $schema) {
                            $schemas[] = $schema->className->relative;
                        }
                    }

                    return $schemas;
                })(...(static function (array $webHooks) {
                    $hooks = [];
                    foreach ($webHooks as $hook) {
                        $hooks = [...$hooks, ...$hook];
                    }

                    return $hooks;
                })($webHooks)),
            )),
            ' */',
        ])))->addParam(
            (new Param('headers'))->setType('array'),
        )->addParam(
            (new Param('data'))->setType('array'),
        );
        $gotoLabels = 'webhooks_aaaaa';
        $tmts       = [];
        $tmts[]     = new Node\Expr\Assign(
            new Node\Expr\Variable('headers'),
            new Node\Expr\FuncCall(
                new Node\Expr\Closure(
                    [
                        'stmts' => [
                            new Node\Stmt\Expression(
                                new Node\Expr\Assign(
                                    new Node\Expr\Variable('flatHeaders'),
                                    new Node\Expr\Array_(),
                                ),
                            ),
                            new Node\Stmt\Foreach_(
                                new Node\Expr\Variable('headers'),
                                new Node\Expr\Variable('value'),
                                [
                                    'keyVar' => new Node\Expr\Variable('key'),
                                    'stmts' => [
                                        new Node\Stmt\Expression(
                                            new Node\Expr\Assign(
                                                new Node\Expr\ArrayDimFetch(
                                                    new Node\Expr\Variable('flatHeaders'),
                                                    new Node\Expr\FuncCall(
                                                        new Node\Name('strtolower'),
                                                        [
                                                            new Arg(
                                                                new Node\Expr\Variable('key'),
                                                            ),
                                                        ],
                                                    ),
                                                ),
                                                new Node\Expr\Variable('value'),
                                            ),
                                        ),
                                    ],
                                ],
                            ),
                            new Node\Stmt\Return_(
                                new Node\Expr\Variable('flatHeaders'),
                            ),
                        ],
                        'params' => [
                            new Node\Param(
                                new Node\Expr\Variable('headers'),
                            ),
                        ],
                        'returnType' => new Node\Name('array'),
                        'static' => true,
                    ],
                ),
                [
                    new Arg(
                        new Node\Expr\Variable('headers'),
                    ),
                ],
            ),
        );
        $tmts[]     = new Node\Expr\Assign(
            new Node\Expr\Variable('error'),
            new Node\Expr\New_(
                new Node\Name('\\' . RuntimeException::class),
                [
                    new Arg(new Node\Scalar\String_('No event matching given headers and data')),
                ],
            ),
        );

        foreach ($webHooks as $event => $hooks) {
            $eventClassname = 'WebHook' . Utils::className($event);
            $eventSanitized = lcfirst((new Convert($event))->toPascal());

            $class->addStmt($factory->property($eventSanitized)->setType('?' . $eventClassname)->setDefault(null)->makePrivate());

            $tmts[] = new Node\Stmt\TryCatch([
                new Node\Stmt\If_(
                    new Node\Expr\BinaryOp\Identical(
                        new Node\Expr\Instanceof_(
                            new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                $eventSanitized,
                            ),
                            new Node\Expr\ConstFetch(new Node\Name($eventClassname)),
                        ),
                        new Node\Expr\ConstFetch(new Node\Name('false')),
                    ),
                    [
                        'stmts' => [
                            new Node\Stmt\Expression(
                                new Node\Expr\Assign(
                                    new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        $eventSanitized,
                                    ),
                                    new Node\Expr\New_(
                                        new Node\Name($eventClassname),
                                        [
                                            new Node\Arg(new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                'requestSchemaValidator',
                                            )),
                                            new Node\Arg(new Node\Expr\MethodCall(
                                                new Node\Expr\PropertyFetch(
                                                    new Node\Expr\Variable('this'),
                                                    'hydrator',
                                                ),
                                                'getObjectMapper' . ucfirst($webHooksHydrators[$event]->methodName),
                                            )),
                                        ],
                                    ),
                                ),
                            ),
                        ],
                    ],
                ),
                new Node\Stmt\Return_(new Node\Expr\MethodCall(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $eventSanitized,
                    ),
                    'resolve',
                    [
                        new Node\Arg(new Node\Expr\Variable('headers')),
                        new Node\Arg(new Node\Expr\Variable('data')),
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
            $tmts[] = new Node\Stmt\Label($gotoLabels);
            $gotoLabels++;
        }

        $tmts[] = new Node\Stmt\Throw_(new Node\Expr\Variable('error'));

        $method->addStmts($tmts);
        $class->addStmt($method);

        yield new File($pathPrefix, 'WebHooks', $stmt->addStmt($class)->getNode());
    }
}
