<?php

namespace ApiClients\Tools\OpenApiClientGenerator\ContentType;

use ApiClients\Tools\OpenApiClientGenerator\Contract\ContentType;
use PhpParser\Node\Expr;
use PhpParser\Node;
use PhpParser\Node\Arg;

final class Json implements ContentType
{
    public static function contentType(): iterable
    {
        yield 'application/json';
    }

    public static function parse(Expr $expr): Expr {
        return new Node\Expr\FuncCall(
            new Node\Name('json_decode'),
            [
                new Arg(
                    $expr,
                ),
                new Node\Expr\ConstFetch(
                    new Node\Name('true'),
                )
            ],
        );
    }
}
