<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Contract;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook;
use PhpParser\Node\Expr;

interface ContentType
{
    /**
     * @return iterable<string>
     */
    public static function contentType(): iterable;

    public static function parse(Expr $expr): Expr;
}
