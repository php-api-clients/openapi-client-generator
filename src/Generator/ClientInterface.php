<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
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
     * @param string $namespace
     * @param array<\ApiClients\Tools\OpenApiClientGenerator\Representation\Operation> $paths
     * @return iterable
     */
    public static function generate(string $pathPrefix, string $namespace, array $operations): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace(trim($namespace, '\\'));

        $class = $factory->interface('ClientInterface');
//        $rawCallReturnTypes = [];
//        $operationCalls = [];
//        $callReturnTypes = [];
//
//        foreach ($clients as $operationGroup => $operations) {
//            $cn = str_replace('/', '\\', '\\' . $namespace . 'Operation/' . $operationGroup);
//            $casedOperationgroup = lcfirst($operationGroup);
//            foreach ($operations as $operationOperation => $operationDetails) {
//                $returnType = [];
//                foreach ($operationDetails['operation']->responses as $code => $spec) {
//                    $contentTypeCases = [];
//                    foreach ($spec->content as $contentType => $contentTypeSchema) {
//                        $fallbackName = 'Operation\\' . $operationGroup . '\\Response\\' . (new Convert(str_replace('/', '\\', $contentType) . '\\H' . $code ))->toPascal();
//                        $object = '\\' . $namespace . 'Schema\\' . $schemaRegistry->get($contentTypeSchema->schema, $fallbackName);
//                        $callReturnTypes[] = ($contentTypeSchema->schema->type === 'array' ? '\\' . Observable::class . '<' : '') . $object . ($contentTypeSchema->schema->type === 'array' ? '>' : '');
//                        $rawCallReturnTypes[] = $contentTypeCases[] = $returnType[] = $contentTypeSchema->schema->type === 'array' ? '\\' . Observable::class : $object;
//                    }
//                    if (count($contentTypeCases) === 0) {
//                        $rawCallReturnTypes[] = $returnType[] = $callReturnTypes[] = 'int';
//                    }
//                }
//                $operationCalls[] = [
//                    'operationGroupMethod' => $casedOperationgroup,
//                    'operationMethod' => lcfirst($operationOperation),
//                    'className' => str_replace('/', '\\', '\\' . $namespace . 'Operation\\' . $operationDetails['class']),
//                    'params' => iterator_to_array((function (array $operationDetails): iterable {
//                        foreach ($operationDetails['operation']->parameters as $parameter) {
//                            yield $parameter->name;
//                        }
//                    })($operationDetails)),
//                    'returnType' => $returnType,
//                ];
//            }
//            $class->addStmt(
//                $factory->method($casedOperationgroup)->setReturnType($cn)->makePublic()
//            );
//        }
//
        $class->addStmt(
            $factory->method('call')->makePublic()->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @return ' . (function (array $operations): string {
                        $count = count($operations);
                        $lastItem = $count - 1;
                        $left = '';
                        $right = '';
                        for ($i = 0; $i < $count; $i++) {
                            $returnType = implode('|', [
                                ...($operations[$i]->matchMethod === 'STREAM' ? ['iterable<string>'] : []),
                                ...array_map(static fn (string $className): string => strpos($className, '\\') === 0 ? $className : 'Schema\\' . $className, array_unique($operations[$i]->returnType)),
                            ]);
                            $returnType = ($operations[$i]->matchMethod === 'LIST' ? 'iterable<' . $returnType . '>' : $returnType);
                            if ($i !== $lastItem) {
                                $left .= '($call is ' . 'Operation\\' . $operations[$i]->classNameSanitized . '::OPERATION_MATCH ? ' . $returnType . ' : ';
                            } else {
                                $left .= $returnType;
                            }
                            $right .= ')';
                        }
                        return $left . $right;
                    })($operations),
                    ' */',
                ]))
            )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))
        );

        $class->addStmt(
            $factory->method('callAsync')->makePublic()->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '/**',
                    ' * @return ' . (function (array $operations): string {
                        $count = count($operations);
                        $lastItem = $count - 1;
                        $left = '';
                        $right = '';
                        for ($i = 0; $i < $count; $i++) {
                            $returnType = implode('|', [
                                ...($operations[$i]->matchMethod === 'STREAM' ? ['\\' . Observable::class . '<string>'] : []),
                                ...array_map(static fn (string $className): string => strpos($className, '\\') === 0 ? $className : 'Schema\\' . $className, array_unique($operations[$i]->returnType)),
                            ]);
                            $returnType = ($operations[$i]->matchMethod === 'LIST' ? '\\' . Observable::class . '<' . $returnType . '>' : $returnType);
                            if ($i !== $lastItem) {
                                $left .= '($call is ' . 'Operation\\' . $operations[$i]->classNameSanitized . '::OPERATION_MATCH ? ' . '\\' . PromiseInterface::class . '<' . $returnType . '>' . ' : ';
                            } else {
                                $left .= '\\' . PromiseInterface::class . '<' . $returnType . '>';
                            }
                            $right .= ')';
                        }
                        return $left . $right;
                    })($operations),
                    ' */',
                ]))
            )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))
        );
//
//        $class->addStmt(
//            $factory->method('hydrateObject')->makePublic()->setDocComment(
//                new Doc(implode(PHP_EOL, [
//                    '/**',
//                    ' * @template H',
//                    ' * @param class-string<H> $className',
//                    ' * @return H',
//                    ' */',
//                ]))
//            )->setReturnType('object')->addParam(
//                (new Param('className'))->setType('string')
//            )->addParam(
//                (new Param('data'))->setType('array')
//            )
//        );

        $class->addStmt(
            $factory->method('webHooks')->setReturnType('\\' . WebHooksInterface::class)->makePublic()
        );

        yield new File($pathPrefix, $namespace . 'ClientInterface', $stmt->addStmt($class)->getNode());
    }
}
