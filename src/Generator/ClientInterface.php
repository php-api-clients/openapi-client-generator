<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\SchemaRegistry;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use cebe\openapi\spec\PathItem;
use Jawira\CaseConverter\Convert;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\Request;
use Rx\Observable;

final class ClientInterface
{
    /**
     * @return iterable<Node>
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public static function generate(string $namespace, array $clients, SchemaRegistry $schemaRegistry): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace(rtrim($namespace, '\\'));

        $class = $factory->interface('ClientInterface');
        $operationCalls = [];
        $callReturnTypes = [];

        foreach ($clients as $operationGroup => $operations) {
            $cn = str_replace('/', '\\', '\\' . $namespace . 'Operation/' . $operationGroup);
            $casedOperationgroup = lcfirst($operationGroup);
            foreach ($operations as $operationOperation => $operationDetails) {
                $returnType = [];
                foreach ($operationDetails['operation']->responses as $code => $spec) {
                    $contentTypeCases = [];
                    foreach ($spec->content as $contentType => $contentTypeSchema) {
                        $fallbackName = 'Operation\\' . $operationGroup . '\\Response\\' . (new Convert(str_replace('/', '\\', $contentType) . '\\H' . $code ))->toPascal();
                        $object = '\\' . $namespace . 'Schema\\' . $schemaRegistry->get($contentTypeSchema->schema, $fallbackName);
                        $callReturnTypes[] = ($contentTypeSchema->schema->type === 'array' ? '\\' . Observable::class . '<' : '') . $object . ($contentTypeSchema->schema->type === 'array' ? '>' : '');
                        $contentTypeCases[] = $returnType[] = $contentTypeSchema->schema->type === 'array' ? '\\' . Observable::class : $object;
                    }
                    if (count($contentTypeCases) === 0) {
                        $returnType[] = $callReturnTypes[] = 'int';
                    }
                }
                $operationCalls[] = [
                    'operationGroupMethod' => $casedOperationgroup,
                    'operationMethod' => lcfirst($operationOperation),
                    'className' => str_replace('/', '\\', '\\' . $namespace . 'Operation\\' . $operationDetails['class']),
                    'params' => iterator_to_array((function (array $operationDetails): iterable {
                        foreach ($operationDetails['operation']->parameters as $parameter) {
                            yield $parameter->name;
                        }
                    })($operationDetails)),
                    'returnType' => $returnType,
                ];
            }
            $class->addStmt(
                $factory->method($casedOperationgroup)->setReturnType($cn)->makePublic()
            );
        }

        $class->addStmt(
            $factory->method('call')->makePublic()->setReturnType(
                new Node\Name('\\' . PromiseInterface::class)
            )->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @return \\' . PromiseInterface::class . '<' . implode('|', array_unique($callReturnTypes)) . '>',
                    ' */',
                ]))
            )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))
        );

        $class->addStmt(
            $factory->method('hydrateObject')->makePublic()->setReturnType('object')->addParam(
                (new Param('className'))->setType('string')
            )->addParam(
                (new Param('data'))->setType('array')
            )
        );

        yield new File($namespace . '\\' . 'ClientInterface', $stmt->addStmt($class)->getNode());
    }
}
