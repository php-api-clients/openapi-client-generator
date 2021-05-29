<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Path;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Schema;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Jawira\CaseConverter\Convert;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

final class File
{
    private string $fqcn;
    private Node $contents;

    public function __construct(string $path, Node $contents)
    {
        $this->fqcn = $path;
        $this->contents = $contents;
    }

    public function fqcn(): string
    {
        return $this->fqcn;
    }

    public function contents(): Node
    {
        return $this->contents;
    }
}
