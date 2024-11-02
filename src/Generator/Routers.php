<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers as ClientRouters;
use OpenAPITools\Contract\Package;
use OpenAPITools\Utils\File;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use React\Http\Browser;

use function trim;

final class Routers
{
    /** @return iterable<File> */
    public static function generate(Package $package, ClientRouters $routers): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim($package->namespace->source, '\\') . '\\Internal');

        $class = $factory->class('Routers')->makeFinal()->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                $factory->param('authentication')->makePrivate()->setType('\\' . AuthenticationInterface::class)->makeReadonly(),
            )->addParam(
                $factory->param('browser')->makePrivate()->setType('\\' . Browser::class)->makeReadonly(),
            )->addParam(
                $factory->param('requestSchemaValidator')->makePrivate()->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly(),
            )->addParam(
                $factory->param('responseSchemaValidator')->makePrivate()->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly(),
            )->addParam(
                $factory->param('hydrators')->makePrivate()->setType('Hydrators')->makeReadonly(),
            ),
        );

        foreach ($routers->get() as $group) {
            $router = $routers->createClassName($package, $group->method, $group->group, '');
            $class->addStmts([
                $factory->property($router->loopUpMethod)->setType('?' . $router->class->fullyQualified->source)->setDefault(null)->makePrivate(),
                $factory->method($router->loopUpMethod)->setReturnType($router->class->fullyQualified->source)->makePublic()->addStmts([
                    new Node\Stmt\If_(
                        new Node\Expr\BinaryOp\Identical(
                            new Node\Expr\Instanceof_(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    $router->loopUpMethod,
                                ),
                                new Node\Name($router->class->fullyQualified->source),
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
                                            new Node\Name($router->class->fullyQualified->source),
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

        yield new File($package->destination->source, 'Internal\\Routers', $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }
}
