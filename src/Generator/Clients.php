<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use RingCentral\Psr7\Request;

final class Clients
{
    /**
     * @param array<string, string> $operations
     * @return iterable<Node>
     */
    public static function generate(string $operationGroup, string $namespace, string $className, array $operations): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal()->addStmt(
            $factory->property('requestSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->property('responseSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()
        )->addStmt(
            $factory->method('__construct')->makePublic()->addParam(
                (new Param('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'requestSchemaValidator'
                    ),
                    new Node\Expr\Variable('requestSchemaValidator'),
                )
            )->addParam(
                (new Param('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'responseSchemaValidator'
                    ),
                    new Node\Expr\Variable('responseSchemaValidator'),
                )
            )
        );

        foreach ($operations as $operationOperation => $operationDetails) {
            $params = [];
            $params[] = new Node\Expr\PropertyFetch(
                new Node\Expr\Variable('this'),
                'requestSchemaValidator'
            );
            $params[] = new Node\Expr\PropertyFetch(
                new Node\Expr\Variable('this'),
                'responseSchemaValidator'
            );
            $cn = str_replace('/', '\\', '\\' . $namespace . '\\' . $operationDetails['class']);
            $method = $factory->method(lcfirst($operationOperation))->setReturnType($cn)->makePublic();
            foreach ($operationDetails['operation']->parameters as $parameter) {
                $params[] = new Node\Arg(new Node\Expr\Variable($parameter->name));
                $param = new Param($parameter->name);
                if ($parameter->schema->type !== null) {
                    $param->setType(
                        str_replace([
                            'integer',
                            'any',
                            'boolean',
                        ], [
                            'int',
                            '',
                            'bool',
                        ], implode('|', is_array($parameter->schema->type) ? $parameter->schema->type : [$parameter->schema->type]))
                    );
                }
                if ($parameter->schema->default !== null) {
                    $param->setDefault($parameter->schema->default);
                }
                $method->addParam($param);
            }
            $class->addStmt($method->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\New_(
                        new Node\Name(
                            $cn
                        ),
                        $params
                    )
                )
            ));
        }

        yield new File($namespace . '\\' . $className, $stmt->addStmt($class)->getNode());
    }
}
