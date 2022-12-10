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

        $class = $factory->class('Client')->makeFinal()->addStmt(
            $factory->property('requestSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('responseSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->method('__construct')->makePublic()->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'requestSchemaValidator'
                    ),
                    new Node\Expr\New_(
                        new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                        [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                                new Node\Name('VALIDATE_AS_REQUEST'),
                            ))
                        ]
                    ),
                )
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'responseSchemaValidator'
                    ),
                    new Node\Expr\New_(
                        new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                        [
                            new Node\Arg(new Node\Expr\ClassConstFetch(
                                new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                                new Node\Name('VALIDATE_AS_RESPONSE'),
                            ))
                        ]
                    ),
                )
            )
        );


        foreach ($clients as $operationGroup => $operations) {
            $cn = str_replace('/', '\\', '\\' . $namespace . 'Operation/' . $operationGroup);
            $class->addStmt(
                $factory->method(lcfirst($operationGroup))->setReturnType($cn)->addStmt(
                    new Node\Stmt\Return_(
                        new Node\Expr\New_(
                            new Node\Name(
                                $cn
                            ),
                            [
                                new Node\Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'requestSchemaValidator'
                                )),
                                new Node\Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'responseSchemaValidator'
                                )),
                            ]
                        )
                    )
                )->makePublic()
            );
        }


        yield new File($namespace . '\\' . 'Client', $stmt->addStmt($class)->getNode());
    }
}
