<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\PHPStan;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\File;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use React\Http\Browser;

use function str_replace;
use function trim;

final class ClientCallReturnTypesTest
{
    /** @return iterable<File> */
    public static function generate(Package $package, Namespaced\Client $client): iterable
    {
        $operations = [];
        foreach ($client->paths as $path) {
            $operations = [...$operations, ...$path->operations];
        }

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(new Node\Name(trim($package->namespace->test . '\\Types', '\\')));

        $stmt->addStmt(
            new Node\Stmt\Expression(
                new Expr\Assign(
                    new Expr\Variable(
                        new Node\Name(
                            'client',
                        ),
                    ),
                    new Expr\New_(
                        new Node\Name(
                            '\\' . $package->namespace->source . '\\Client',
                        ),
                        [
                            new Arg(
                                new Expr\New_(
                                    new Node\Stmt\Class_(
                                        null,
                                        [
                                            'implements' => [
                                                new Node\Name('\\' . AuthenticationInterface::class),
                                            ],
                                            'stmts' => [
                                                $factory->method('authHeader')->setReturnType(
                                                    new Node\Name('string'),
                                                )->addStmt(
                                                    new Node\Stmt\Return_(
                                                        new Node\Scalar\String_('Saturn V'),
                                                    ),
                                                )->getNode(),
                                            ],
                                        ],
                                    ),
                                ),
                            ),
                            new Arg(
                                new Expr\New_(
                                    new Node\Name(
                                        '\\' . Browser::class,
                                    ),
                                ),
                            ),
                        ],
                    ),
                ),
            ),
        );

        foreach ($operations as $operation) {
            $stmt->addStmt(
                new Node\Stmt\Expression(
                    new Expr\FuncCall(
                        new Node\Name(
                            '\PHPStan\Testing\assertType',
                        ),
                        [
                            new Arg(
                                new Node\Scalar\String_(
                                    str_replace(',', ', ', Operation::getDocBlockResultTypeFromOperation($operation)),
                                ),
                            ),
                            new Arg(
                                new Expr\MethodCall(
                                    new Expr\Variable(
                                        new Node\Name(
                                            'client',
                                        ),
                                    ),
                                    new Node\Name(
                                        'call',
                                    ),
                                    [
                                        new Arg(
                                            new Node\Scalar\String_($operation->matchMethod . ' ' . $operation->path),
                                        ),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            );
        }

        yield new File($package->destination->test, 'Types\\ClientCallReturnTypes', $stmt->getNode(), File::DO_NOT_LOAD_ON_WRITE);
    }
}
