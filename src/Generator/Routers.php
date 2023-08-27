<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers as ClientRouters;
use ApiClients\Tools\OpenApiClientGenerator\PrivatePromotedPropertyAsParam;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use React\Http\Browser;

use function trim;

final class Routers
{
    /** @return iterable<File> */
    public static function generate(Configuration $configuration, string $pathPrefix, ClientRouters $routers): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim($configuration->namespace->source, '\\') . '\\Internal');

        $class = $factory->class('Routers')->makeFinal()->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new PrivatePromotedPropertyAsParam('authentication'))->setType('\\' . AuthenticationInterface::class)->makeReadonly(),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('browser'))->setType('\\' . Browser::class)->makeReadonly(),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly(),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly(),
            )->addParam(
                (new PrivatePromotedPropertyAsParam('hydrators'))->setType('Internal\\Hydrators')->makeReadonly(),
            ),
        );

        foreach ($routers->get() as $group) {
            $router = $routers->createClassName($group->method, $group->group, '');
            $class->addStmts([
                $factory->property($router->loopUpMethod)->setType('?' . $router->class)->setDefault(null)->makePrivate(),
                $factory->method($router->loopUpMethod)->setReturnType($router->class)->makePublic()->addStmts([
                    new Node\Stmt\If_(
                        new Node\Expr\BinaryOp\Identical(
                            new Node\Expr\Instanceof_(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    $router->loopUpMethod,
                                ),
                                new Node\Name($router->class),
                            ),
                            new Node\Expr\ConstFetch(new Node\Name('false')),
                        ),
                        [
                            'stmts' => [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Assign(
                                        new Node\Expr\PropertyFetch(
                                            new Node\Expr\Variable('this'),
                                            $router->loopUpMethod,
                                        ),
                                        new Node\Expr\New_(
                                            new Node\Name($router->class),
                                            [
                                                new Arg(
                                                    new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'browser',
                                                    ),
                                                    false,
                                                    false,
                                                    [],
                                                    new Node\Identifier('browser'),
                                                ),
                                                new Arg(
                                                    new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'authentication',
                                                    ),
                                                    false,
                                                    false,
                                                    [],
                                                    new Node\Identifier('authentication'),
                                                ),
                                                new Arg(
                                                    new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'requestSchemaValidator',
                                                    ),
                                                    false,
                                                    false,
                                                    [],
                                                    new Node\Identifier('requestSchemaValidator'),
                                                ),
                                                new Arg(
                                                    new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'responseSchemaValidator',
                                                    ),
                                                    false,
                                                    false,
                                                    [],
                                                    new Node\Identifier('responseSchemaValidator'),
                                                ),
                                                new Arg(
                                                    new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        'hydrators',
                                                    ),
                                                    false,
                                                    false,
                                                    [],
                                                    new Node\Identifier('hydrators'),
                                                ),
                                            ],
                                        ),
                                    ),
                                ),
                            ],
                        ],
                    ),
                    new Node\Stmt\Return_(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            $router->loopUpMethod,
                        ),
                    ),
                ]),
            ]);
        }

        yield new File($pathPrefix, 'Internal\\Routers', $stmt->addStmt($class)->getNode());
    }
}
