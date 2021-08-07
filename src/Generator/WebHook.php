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

final class WebHook
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
    public static function generate(string $path, string $namespace, string $baseNamespace, string $className, PathItem $pathItem, array $schemaClassNameMap, string $rootNamespace): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal()->implement('\\' . $baseNamespace . 'WebHookInterface');

        $method = $factory->method('resolve')->makePublic()->setReturnType('string')->addParam(
            (new Param('data'))->setType('array')
        );
        if ($pathItem->post->requestBody->content !== null) {
            $content = current($pathItem->post->requestBody->content);
            $tmts = [];
            if ($content->schema->oneOf !== null && count($content->schema->oneOf) > 0) {
                $tmts[] = new Node\Expr\Assign(new Node\Expr\Variable('schemaValidator'), new Node\Expr\New_(
                    new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                    [
                        new Node\Arg(new Node\Expr\ClassConstFetch(
                            new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                            new Node\Name('VALIDATE_AS_REQUEST'),
                        ))
                    ]
                ));
                $gotoLabels = 'a';
                foreach ($content->schema->oneOf as $oneOfSchema) {
                    $tmts[] = new Node\Stmt\Label($gotoLabels);
                    $gotoLabels++;
                    $tmts[] = new Node\Stmt\TryCatch([
                        new Node\Stmt\Expression(new Node\Expr\MethodCall(
                            new Node\Expr\Variable('schemaValidator'),
                            new Node\Name('validate'),
                            [
                                new Node\Arg(new Node\Expr\Variable('data')),
                                new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), new Node\Name('readFromJson'), [new Node\Scalar\String_(json_encode($oneOfSchema->getSerializableData())), new Node\Scalar\String_('\cebe\openapi\spec\Schema')])),
                            ]
                        )),
                        new Node\Stmt\Return_(new Node\Scalar\String_($rootNamespace . 'Schema\\' . $schemaClassNameMap[spl_object_hash($oneOfSchema)])),
                    ], [
                        new Node\Stmt\Catch_(
                            [new Node\Name('\\' . \Throwable::class)],
                            new Node\Expr\Variable($gotoLabels),
                            [
                                new Node\Stmt\Goto_($gotoLabels),
                            ]
                        ),
                    ]);
                }
                $tmts[] = new Node\Stmt\Label($gotoLabels);
                $tmts[] = new Node\Stmt\Throw_(new Node\Expr\Variable($gotoLabels));
            }

            if (count($tmts) === 0) {
                return;
            }

            $method->addStmts($tmts);
        }
        $class->addStmt($method);

        yield new File($namespace . '\\' . $className, $stmt->addStmt($class)->getNode());
    }
}
