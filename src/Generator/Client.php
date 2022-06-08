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

final class Client
{
    /**
     * @param array<string, string> $operations
     * @return iterable<Node>
     */
    public static function generate(string $operationGroup, string $namespace, string $className, array $operations): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal();

        foreach ($operations as $operationOperation => $operationDetails) {
            $params = [];
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
                        ], $parameter->schema->type)
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
