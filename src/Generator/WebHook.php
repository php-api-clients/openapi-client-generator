<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\OpenAPI\WebHookInterface;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use League\OpenAPIValidation\Schema\SchemaValidator;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use RuntimeException;
use Throwable;

use function array_unique;
use function count;
use function implode;
use function ltrim;
use function strtolower;

use const PHP_EOL;

final class WebHook
{
    /**
     * @param class-string $event
     *
     * @return iterable<File>
     */
    public static function generate(
        string $pathPrefix,
        string $namespace,
        string $event,
        SchemaRegistry $schemaRegistry,
        \ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook ...$webHooks,
    ): iterable {
        $className = Utils::className($event);

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(ltrim($namespace . 'Internal\WebHook', '\\'));

        $class = $factory->class(ltrim($className, '\\'))->makeFinal()->implement('\\' . WebHookInterface::class)->setDocComment(new Doc(implode(PHP_EOL, [
            '/**',
            ' * @internal',
            ' */',
        ])));
        $class->addStmt($factory->property('requestSchemaValidator')->setType('\\' . SchemaValidator::class)->makeReadonly()->makePrivate());
        $class->addStmt($factory->property('hydrator')->setType('Internal\\Hydrator\\WebHook\\' . $className)->makeReadonly()->makePrivate());

        $constructor = $factory->method('__construct')->makePublic()->addParam(
            (new Param('requestSchemaValidator'))->setType('\\' . SchemaValidator::class),
        )->addParam(
            (new Param('hydrator'))->setType('Internal\\Hydrator\\WebHook\\' . $className),
        )->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'requestSchemaValidator',
                ),
                new Node\Expr\Variable('requestSchemaValidator'),
            ),
        )->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'hydrator',
                ),
                new Node\Expr\Variable('hydrator'),
            ),
        );
        $class->addStmt($constructor);

        $resolveReturnTypes = [];
        $method             = $factory->method('resolve')->makePublic()->setReturnType('object')->addParam(
            (new Param('headers'))->setType('array'),
        )->addParam(
            (new Param('data'))->setType('array'),
        );
        $gotoLabels         = 'actions_aaaaa';
        $tmts               = [];
        $tmts[]             = new Node\Expr\Assign(
            new Node\Expr\Variable('error'),
            new Node\Expr\New_(
                new Node\Name('\\' . RuntimeException::class),
                [
                    new Arg(new Node\Scalar\String_('No action matching given headers and data')),
                ],
            ),
        );

        foreach ($webHooks as $webHook) {
            $headers = [];
            foreach ($webHook->headers as $header) {
                $headers[] = new Node\Stmt\Expression(new Node\Expr\MethodCall(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'requestSchemaValidator',
                    ),
                    'validate',
                    [
                        new Node\Arg(new Node\Expr\ArrayDimFetch(
                            new Node\Expr\Variable('headers'),
                            new Node\Scalar\String_(strtolower($header->name)),
                        )),
                        new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), 'readFromJson', [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name($header->schema->className->relative),
                                'SCHEMA_JSON',
                            )),
                            new Node\Arg(new Node\Scalar\String_('\cebe\openapi\spec\Schema')),
                        ])),
                    ],
                ));
            }

            foreach ($webHook->schema as $contentTYpe => $schema) {
                $resolveReturnTypes[] = $schema->className->relative;
                $tmts[]               = new Node\Stmt\If_(
                    new Node\Expr\BinaryOp\Equal(
                        new Node\Expr\ArrayDimFetch(new Node\Expr\Variable('headers'), new Node\Scalar\String_('content-type')),
                        new Node\Scalar\String_($contentTYpe),
                    ),
                    [
                        'stmts' => [
                            new Node\Stmt\TryCatch([
                                ...$headers,
                                new Node\Stmt\Expression(new Node\Expr\MethodCall(
                                    new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        'requestSchemaValidator',
                                    ),
                                    'validate',
                                    [
                                        new Node\Arg(new Node\Expr\Variable('data')),
                                        new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), 'readFromJson', [
                                            new Arg(new Node\Expr\ClassConstFetch(
                                                new Node\Name($schema->className->relative),
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
                                            new Node\Name($schema->className->relative),
                                            'class',
                                        )),
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
                            ]),
                        ],
                    ],
                );
            }

            $tmts[] = new Node\Stmt\Label($gotoLabels);
            $gotoLabels++;
        }

        $tmts[] = new Node\Stmt\Throw_(new Node\Expr\Variable('error'));

        if (count($resolveReturnTypes) > 0) {
            $method->setReturnType(implode('|', array_unique($resolveReturnTypes)));
        }

        $method->addStmts($tmts);
        $class->addStmt($method);

        yield new File($pathPrefix, 'Internal\\WebHook\\' . $className, $stmt->addStmt($class)->getNode());
    }
}
