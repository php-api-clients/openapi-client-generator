<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\ContentType;

use ApiClients\Tools\OpenApiClientGenerator\Contract\ContentType;
use PhpParser\Node\Expr;

final class Raw implements ContentType
{
    /** @return iterable<string> */
    public static function contentType(): iterable
    {
        yield 'text/plain';
        yield 'text/x-markdown';
        yield 'text/html';
        yield 'application/pdf';
    }

    public static function parse(Expr $expr): Expr
    {
        return $expr;
    }
}
