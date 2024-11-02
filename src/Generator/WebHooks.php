<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\ConfigurationPackage;
use League\OpenAPIValidation\Schema\SchemaValidator;
use OpenAPITools\Contract\FileGenerator;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\ClassString;
use OpenAPITools\Utils\File;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use RuntimeException;
use Throwable;

use function lcfirst;
use function ucfirst;

/**
 * Emits the package's public webhook entry point.
 *
 * Which event a delivery belongs to is not known up front, so resolving walks
 * the event classes and returns the first that accepts the payload. Event
 * classes are built on demand: a package can carry dozens of them, and a given
 * delivery only ever needs one.
 */
final readonly class WebHooks implements FileGenerator
{
    public function __construct(private BuilderFactory $builderFactory)
    {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Namespaced\Representation $representation): iterable
    {
        $package = ConfigurationPackage::unwrap($package);

        if ($representation->webHooks === []) {
            return;
        }

        $className          = ClassString::factory($package->namespace, 'WebHooks');
        $hydratorsClassName = ClassString::factory($package->namespace, 'Internal\\Hydrators');

        $class = $this->builderFactory->class($className->className)
            ->makeFinal()
            ->implement('\\' . WebHooksInterface::class)
            ->addStmt(
                $this->builderFactory->method('__construct')->makePublic()->addParam(
                    $this->builderFactory->param('requestSchemaValidator')->makePrivate()->makeReadonly()->setType('\\' . SchemaValidator::class),
                )->addParam(
                    $this->builderFactory->param('hydrator')->makePrivate()->makeReadonly()->setType($hydratorsClassName->fullyQualified->source),
                ),
            );

        $resolve = $this->builderFactory->method('resolve')->makePublic()->setReturnType('object')->addParams([
            $this->builderFactory->param('headers')->setType('array'),
            $this->builderFactory->param('data')->setType('array'),
        ]);

        /**
         * Header names are case insensitive over the wire, so they are lowered
         * once here rather than at each comparison further down the chain.
         */
        $resolve->addStmt(
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('headers'),
                    new Node\Expr\FuncCall(
                        new Node\Expr\Closure([
                            'static' => true,
                            'params' => [$this->builderFactory->param('headers')->setType('array')->getNode()],
                            'returnType' => new Node\Identifier('array'),
                            'stmts' => [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Assign(new Node\Expr\Variable('loweredHeaders'), new Node\Expr\Array_([])),
                                ),
                                new Node\Stmt\Foreach_(
                                    new Node\Expr\Variable('headers'),
                                    new Node\Expr\Variable('value'),
                                    [
                                        'keyVar' => new Node\Expr\Variable('key'),
                                        'stmts' => [
                                            new Node\Stmt\Expression(
                                                new Node\Expr\Assign(
                                                    new Node\Expr\ArrayDimFetch(
                                                        new Node\Expr\Variable('loweredHeaders'),
                                                        new Node\Expr\FuncCall(new Node\Name('strtolower'), [
                                                            new Arg(new Node\Expr\Variable('key')),
                                                        ]),
                                                    ),
                                                    new Node\Expr\Variable('value'),
                                                ),
                                            ),
                                        ],
                                    ],
                                ),
                                new Node\Stmt\Return_(new Node\Expr\Variable('loweredHeaders')),
                            ],
                        ]),
                        [new Arg(new Node\Expr\Variable('headers'))],
                    ),
                ),
            ),
        );

        $resolve->addStmt(
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('error'),
                    new Node\Expr\New_(new Node\Name('\\' . RuntimeException::class), [
                        new Arg(new Node\Scalar\String_('No webhook matching given headers and data')),
                    ]),
                ),
            ),
        );

        foreach ($representation->webHooks as $webHookEvent) {
            $eventClassName = ClassString::factory($package->namespace, 'Internal\\WebHook\\' . $webHookEvent->event);
            $propertyName   = lcfirst($eventClassName->className);

            $class->addStmt(
                $this->builderFactory->property($propertyName)
                    ->setType('?' . $eventClassName->fullyQualified->source)
                    ->setDefault(null)
                    ->makePrivate(),
            );

            $resolve->addStmt(
                new Node\Stmt\TryCatch(
                    [
                        new Node\Stmt\If_(
                            new Node\Expr\BinaryOp\Identical(
                                new Node\Expr\Instanceof_(
                                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                                    new Node\Name($eventClassName->fullyQualified->source),
                                ),
                                new Node\Expr\ConstFetch(new Node\Name('false')),
                            ),
                            [
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                                            new Node\Expr\New_(
                                                new Node\Name($eventClassName->fullyQualified->source),
                                                [
                                                    new Arg(new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'requestSchemaValidator')),
                                                    new Arg(new Node\Expr\MethodCall(
                                                        new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'hydrator'),
                                                        'getObjectMapper' . ucfirst($webHookEvent->hydrator->methodName),
                                                    )),
                                                ],
                                            ),
                                        ),
                                    ),
                                ],
                            ],
                        ),
                        new Node\Stmt\Return_(
                            new Node\Expr\MethodCall(
                                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                                'resolve',
                                [
                                    new Arg(new Node\Expr\Variable('headers')),
                                    new Arg(new Node\Expr\Variable('data')),
                                ],
                            ),
                        ),
                    ],
                    [
                        new Node\Stmt\Catch_(
                            [new Node\Name('\\' . Throwable::class)],
                            new Node\Expr\Variable('throwable'),
                            [
                                new Node\Stmt\Expression(
                                    new Node\Expr\Assign(
                                        new Node\Expr\Variable('error'),
                                        new Node\Expr\Variable('throwable'),
                                    ),
                                ),
                            ],
                        ),
                    ],
                ),
            );
        }

        $resolve->addStmt(new Node\Stmt\Expression(new Node\Expr\Throw_(new Node\Expr\Variable('error'))));

        $class->addStmt(
            $this->builderFactory->method('hydrateWebHook')->makePublic()->setReturnType('object')->addParams([
                $this->builderFactory->param('className')->setType('string'),
                $this->builderFactory->param('data')->setType('array'),
            ])->setDocComment(
                '/**' . "\n" .
                ' * @param class-string<H> $className' . "\n" .
                ' *' . "\n" .
                ' * @return H' . "\n" .
                ' *' . "\n" .
                ' * @template H' . "\n" .
                ' */',
            )->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\MethodCall(
                        new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'hydrator'),
                        'hydrateObject',
                        [
                            new Arg(new Node\Expr\Variable('className')),
                            new Arg(new Node\Expr\Variable('data')),
                        ],
                    ),
                ),
            ),
        );

        $class->addStmt(
            $this->builderFactory->method('serializeWebHook')->makePublic()->setReturnType('array')->addParam(
                $this->builderFactory->param('object')->setType('object'),
            )->setDocComment('/** @return array{className: class-string, data: mixed} */')->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\Array_([
                        new Node\Expr\ArrayItem(
                            new Node\Expr\ClassConstFetch(new Node\Expr\Variable('object'), 'class'),
                            new Node\Scalar\String_('className'),
                        ),
                        new Node\Expr\ArrayItem(
                            new Node\Expr\MethodCall(
                                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'hydrator'),
                                'serializeObject',
                                [new Arg(new Node\Expr\Variable('object'))],
                            ),
                            new Node\Scalar\String_('data'),
                        ),
                    ]),
                ),
            ),
        );

        $class->addStmt($resolve);

        yield new File(
            $package->destination->source,
            $className->relative,
            $this->builderFactory->namespace($className->namespace->source)->addStmt($class)->getNode(),
            File::DO_LOAD_ON_WRITE,
        );
    }
}
