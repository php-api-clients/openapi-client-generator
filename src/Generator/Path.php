<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
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
    /**
     * @param string $path
     * @param string $namespace
     * @param string $baseNamespace
     * @param string $className
     * @param PathItem $pathItem
     * @return iterable<Node>
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public static function generate(string $path, string $namespace, string $baseNamespace, string $className, PathItem $pathItem): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal();

        foreach ($pathItem->getOperations() as $method => $operation) {
            $operationClassName = str_replace('/', '\\', '\\' . $baseNamespace . 'Operation/' . (new Convert($operation->operationId))->fromTrain()->toPascal());
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
                        ], implode('|', is_array($parameter->schema->type) ? $parameter->schema->type : [$parameter->schema->type]))
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

        yield new File($namespace . '\\' . $className, $stmt->addStmt($class)->getNode());
    }
}
