<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\ContentType;

use ApiClients\Tools\OpenApiClientGenerator\Contract\ContentType;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;

final class Json implements ContentType
{
    /**
     * @return iterable<string>
     */
    public static function contentType(): iterable
    {
        yield 'application/json';
    }

    public static function parse(Expr $expr): Expr
    {
        return new Node\Expr\FuncCall(
            new Node\Name('json_decode'),
            [
                new Arg(
                    $expr,
                ),
                new Arg(
                    new Node\Expr\ConstFetch(
                        new Node\Name('true'),
                    ),
                ),
            ],
        );
    }
}
