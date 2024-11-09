<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Types;
use OpenAPITools\Contract\FileGenerator;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\File;
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

final class ClientInterface implements FileGenerator
{
    public function __construct(
        private BuilderFactory $builderFactory,
        private bool $call,
        private bool $operations,
    ) {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Namespaced\Representation $representation): iterable
    {
        $stmt = $this->builderFactory->namespace(trim($package->namespace->source, '\\'));

        $class = $this->builderFactory->interface('ClientInterface');

        if ($this->call) {
            $class->addStmt(
                $this->builderFactory->method('call')->makePublic()->setDocComment(
                    new Doc(implode(PHP_EOL, [
                        ...($package->qa?->phpcs ?  ['// phpcs:disable'] : []),
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
                        ...($package->qa?->phpcs ?  ['// phpcs:enabled'] : []),
                    ])),
                )->addParam($this->builderFactory->param('call')->setType('string'))->addParam($this->builderFactory->param('params')->setType('array')->setDefault([]))->setReturnType(
                    new UnionType(
                        array_map(
                            static fn (string $type): Name => new Name($type),
                            array_unique(
                                [
                                    ...Types::filterDuplicatesAndIncompatibleRawTypes(...(static function (Namespaced\Path ...$paths): iterable {
                                        foreach ($paths as $path) {
                                            foreach ($path->operations as $operation) {
                                                yield from explode('|', Operation::getResultTypeFromOperation($operation));
                                            }
                                        }
                                    })(...$representation->client->paths)),
                                ],
                            ),
                        ),
                    ),
                ),
            );
        }

        if ($this->operations) {
            $class->addStmt(
                $this->builderFactory->method('operations')->setReturnType('OperationsInterface')->makePublic(),
            );
        }

        yield new File($package->destination->source, 'ClientInterface', $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }
}
