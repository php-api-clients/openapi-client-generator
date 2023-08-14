<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;

use PhpParser\Node;
use PhpParser\Node\Arg;
use Rx\Observable;

final class ResultConverter
{
    /** @return iterable<Node> */
    public static function convert(Node\Expr $expr): iterable
    {
        yield new Node\Stmt\Expression(
            new Node\Expr\Assign(
                new Node\Expr\Variable('result'),
                new Node\Expr\FuncCall(
                    new Node\Name('\React\Async\await'),
                    [
                        new Node\Arg($expr),
                    ],
                ),
            ),
        );

        yield new Node\Stmt\If_(
            new Node\Expr\Instanceof_(
                new Node\Expr\Variable('result'),
                new Node\Name('\\' . Observable::class),
            ),
            [
                'stmts' => [
                    new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Node\Expr\Variable('result'),
                            new Node\Expr\FuncCall(
                                new Node\Name('\WyriHaximus\React\awaitObservable'),
                                [
                                    new Arg(new Node\Expr\Variable('result')),
                                ],
                            ),
                        ),
                    ),
                ],
            ],
        );

        yield new Node\Stmt\Return_(
            new Node\Expr\Variable('result'),
        );
    }
}
