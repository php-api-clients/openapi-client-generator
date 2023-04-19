<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use React\Promise\PromiseInterface;
use Rx\Observable;

use function array_map;
use function array_unique;
use function count;
use function implode;
use function strpos;
use function trim;

use const PHP_EOL;

final class ClientInterface
{
    /**
     * @param array<Operation> $paths
     *
     * @return iterable
     */
    public static function generate(string $pathPrefix, string $namespace, array $operations): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim($namespace, '\\'));

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
        $class->addStmt(
            $factory->method('call')->makePublic()->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '// phpcs:disable',
                    '/**',
                    ' * @return ' . (static function (array $operations): string {
                        $count    = count($operations);
                        $lastItem = $count - 1;
                        $left     = '';
                        $right    = '';
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
                    '// phpcs:enable',
                ]))
            )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))
        );

        $class->addStmt(
            $factory->method('callAsync')->makePublic()->setDocComment(
                new Doc(implode(PHP_EOL, [
                    '// phpcs:disable',
                    '/**',
                    ' * @return ' . (static function (array $operations): string {
                        $count    = count($operations);
                        $lastItem = $count - 1;
                        $left     = '';
                        $right    = '';
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
                    '// phpcs:enable',
                ]))
            )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))
        );
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

        yield new File($pathPrefix, 'ClientInterface', $stmt->addStmt($class)->getNode());
    }
}
