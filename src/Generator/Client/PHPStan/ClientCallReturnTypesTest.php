<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client\PHPStan;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Representation;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use React\Http\Browser;

use function trim;

final class ClientCallReturnTypesTest
{
    /** @return iterable<File> */
    public static function generate(Configuration $configuration, string $pathPrefix, Representation\Client $client): iterable
    {
        $operations = [];
        foreach ($client->paths as $path) {
            $operations = [...$operations, ...$path->operations];
        }

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(new Node\Name(trim($configuration->namespace->test . '\\Types', '\\')));

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
                            '\\' . $configuration->namespace->source . '\\Client',
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
                                    Operation::getDocBlockResultTypeFromOperation($operation),
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

        yield new File($pathPrefix, 'Types\ClientCallReturnTypes', $stmt->getNode());
    }
}
