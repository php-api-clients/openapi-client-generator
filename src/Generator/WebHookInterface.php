<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use cebe\openapi\spec\PathItem;
use Jawira\CaseConverter\Convert;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use RingCentral\Psr7\Request;

final class WebHookInterface
{
    /**
     * @param string $path
     * @param string $namespace
     * @param string $baseNamespace
     * @param string $className
     * @param PathItem $pathItem
     * @return iterable<Node>
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public static function generate(string $namespace, string $className): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        yield new File($namespace . '\\' . $className, $stmt->addStmt($factory->interface($className)->addStmt($factory->method('resolve')->makePublic()->setReturnType('string')->addParam(
            (new Param('data'))->setType('array')
        )))->getNode());
    }
}
