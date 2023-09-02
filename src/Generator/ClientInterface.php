<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Types;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node\Name;
use PhpParser\Node\UnionType;

use function array_map;
use function array_unique;
use function explode;
use function implode;
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
                        ...($configuration->qa?->phpcs ?  ['// phpcs:disable'] : []),
                        '/**',
                    //                        ' * @return ' . (static function (array $operations): string {
                    //                            $count    = count($operations);
                    //                            $lastItem = $count - 1;
                    //                            $left     = '';
                    //                            $right    = '';
                    //                            for ($i = 0; $i < $count; $i++) {
                    //                                $returnType = \ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation::getDocBlockResultTypeFromOperation($operations[$i]);
                    //                                if ($i !== $lastItem) {
                    //                                    $left .= '($call is "' . $operations[$i]->matchMethod . ' ' . $operations[$i]->path . '" ? ' . $returnType . ' : ';
                    //                                } else {
                    //                                    $left .= $returnType;
                    //                                }
                    //
                    //                                $right .= ')';
                    //                            }
                    //
                    //                            return $left . $right;
                    //                        })($operations),
                        ' */',
                        ...($configuration->qa?->phpcs ?  ['// phpcs:enabled'] : []),
                    ])),
                )->addParam((new Param('call'))->setType('string'))->addParam((new Param('params'))->setType('array')->setDefault([]))->setReturnType(
                    new UnionType(
                        array_map(
                            static fn (string $type): Name => new Name($type),
                            array_unique(
                                [
                                    ...Types::filterDuplicatesAndIncompatibleRawTypes(...(static function (array $operations): iterable {
                                        foreach ($operations as $operation) {
                                            yield from explode('|', \ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation::getResultTypeFromOperation($operation));
                                        }
                                    })($operations)),
                                ],
                            ),
                        ),
                    ),
                ),
            );
        }

        if ($configuration->entryPoints->operations) {
            $class->addStmt(
                $factory->method('operations')->setReturnType('OperationsInterface')->makePublic(),
            );
        }

        if ($configuration->entryPoints->webHooks) {
            $class->addStmt(
                $factory->method('webHooks')->setReturnType('\\' . WebHooksInterface::class)->makePublic(),
            );
        }

        yield new File($pathPrefix, 'ClientInterface', $stmt->addStmt($class)->getNode());
    }
}
