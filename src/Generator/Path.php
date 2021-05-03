<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use cebe\openapi\spec\Operation as OpenAPiOperation;
use cebe\openapi\spec\PathItem;
use Jawira\CaseConverter\Convert;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use RingCentral\Psr7\Request;

final class Path
{
    public static function generate(string $path, string $namespace, string $baseNamespace, string $className, PathItem $pathItem): Node
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal();

        foreach ($pathItem->getOperations() as $method => $operation) {
            $operationClassName = str_replace('/', '\\', '\\' . $baseNamespace . 'Operation/' . (new Convert($operation->operationId))->fromTrain()->toPascal()) . 'Operation';
            $method =
                $factory->
                method($method)->
                setReturnType($operationClassName)
            ;
            $operationConstructorArguments = [];
            foreach ($operation->parameters as $parameter) {
                $param = new Param($parameter->name);
                if ($parameter->schema->default !== null) {
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
                $operationConstructorArguments[] = new Node\Expr\Variable($parameter->name);
            }
            $method->addStmt(new Node\Stmt\Return_(new Node\Expr\New_(
                new Node\Name($operationClassName),
                $operationConstructorArguments
            )));
            $class->addStmt($method);
        }

        return $stmt->addStmt($class)->getNode();
    }
}
