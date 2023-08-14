<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Contract;

use PhpParser\Node\Expr;

interface ContentType
{
    /** @return iterable<string> */
    public static function contentType(): iterable;

    public static function parse(Expr $expr): Expr;
}
