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

final class Clients
{
    /**
     * @return iterable<Node>
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public static function generate(string $namespace, array $clients): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace(rtrim($namespace, '\\'));

        $class = $factory->class('Client')->makeFinal();


        foreach ($clients as $operationGroup => $operations) {
            $cn = str_replace('/', '\\', '\\' . $namespace . 'Operation/' . $operationGroup);
            $class->addStmt(
                $factory->method(lcfirst($operationGroup))->setReturnType($cn)->addStmt(
                    new Node\Stmt\Return_(
                        new Node\Expr\New_(
                            new Node\Name(
                                $cn
                            ),
                        )
                    )
                )->makePublic()
            );
        }


        yield new File($namespace . '\\' . 'Client', $stmt->addStmt($class)->getNode());
    }
}
