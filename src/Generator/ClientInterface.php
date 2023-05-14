<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
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
     * @param array<Operation> $operations
     *
     * @return iterable<File>
     */
    public static function generate(Configuration $configuration, string $pathPrefix, array $operations): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim($configuration->namespace->source, '\\'));

        $class = $factory->interface('ClientInterface');

        if ($configuration->entryPoints->call) {
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
                                    $left .= '($call is ' . $operations[$i]->classNameSanitized->relative . '::OPERATION_MATCH ? ' . $returnType . ' : ';
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
                                    $left .= '($call is ' . $operations[$i]->classNameSanitized->relative . '::OPERATION_MATCH ? \\' . PromiseInterface::class . '<' . $returnType . '> : ';
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
        }

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

        if ($configuration->entryPoints->operations) {
            $class->addStmt(
                $factory->method('operations')->setReturnType('OperationsInterface')->makePublic()
            );
        }

        if ($configuration->entryPoints->webHooks) {
            $class->addStmt(
                $factory->method('webHooks')->setReturnType('\\' . WebHooksInterface::class)->makePublic()
            );
        }

        yield new File($pathPrefix, 'ClientInterface', $stmt->addStmt($class)->getNode());
    }
}
