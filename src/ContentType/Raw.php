<?php

namespace ApiClients\Tools\OpenApiClientGenerator\ContentType;

use ApiClients\Tools\OpenApiClientGenerator\Contract\ContentType;
use PhpParser\Node\Expr;
use PhpParser\Node;
use PhpParser\Node\Arg;

final class Raw implements ContentType
{
    public static function contentType(): iterable
    {
        yield 'text/plain';
        yield 'text/x-markdown';
        yield 'text/html';
    }

    public static function parse(Expr $expr): Expr {
        return $expr;
    }
}
