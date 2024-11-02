<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Contracts\OpenAPI\WebHookInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\ConfigurationPackage;
use cebe\openapi\Reader;
use cebe\openapi\spec\Schema as BaseSchema;
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

use function array_map;
use function array_values;

/**
 * Emits one class per webhook event, resolving a delivery to its payload object.
 *
 * A spec declares a webhook per event variant, so an event covers several
 * payload schemas. Each is tried in turn: validate the delivery against that
 * variant's schema, and on success hand it to the event's hydrator. Hydration
 * is delegated rather than inlined, so the payload schemas and their hydrators
 * stay the ones already generated for every other schema in the package.
 */
final readonly class WebHook implements FileGenerator
{
    public function __construct(private BuilderFactory $builderFactory)
    {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Namespaced\Representation $representation): iterable
    {
        $package = ConfigurationPackage::unwrap($package);

        foreach ($representation->webHooks as $webHookEvent) {
            yield $this->generateEvent($package, $webHookEvent);
        }
    }

    private function generateEvent(\OpenAPITools\Configuration\Package $package, Namespaced\WebHookEvent $webHookEvent): File
    {
        $className = ClassString::factory($package->namespace, 'Internal\\WebHook\\' . $webHookEvent->event);

        /** @var array<Namespaced\Schema> $payloads */
        $payloads = [];
        foreach ($webHookEvent->webHooks as $webHook) {
            foreach ($webHook->schema as $schema) {
                $payloads[$schema->className->fullyQualified->source] = $schema;
            }
        }

        $payloads = array_values($payloads);

        $resolve = $this->builderFactory->method('resolve')->makePublic()->addParams([
            $this->builderFactory->param('headers')->setType('array'),
            $this->builderFactory->param('data')->setType('array'),
        ])->setReturnType(
            new Node\UnionType(
                array_map(
                    static fn (Namespaced\Schema $schema): Node\Name => new Node\Name($schema->className->fullyQualified->source),
                    $payloads,
                ),
            ),
        );

        /**
         * Seeded so the method still throws something meaningful when the event
         * has no variants at all; every attempt below replaces it with the
         * reason that attempt was rejected.
         */
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

        foreach ($payloads as $payload) {
            $resolve->addStmt(
                new Node\Stmt\TryCatch(
                    [
                        new Node\Stmt\Expression(
                            new Node\Expr\MethodCall(
                                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'requestSchemaValidator'),
                                'validate',
                                [
                                    new Arg(new Node\Expr\Variable('data')),
                                    new Arg(new Node\Expr\StaticCall(new Node\Name('\\' . Reader::class), 'readFromJson', [
                                        new Arg(new Node\Expr\ClassConstFetch(
                                            new Node\Name($payload->className->fullyQualified->source),
                                            'SCHEMA_JSON',
                                        )),
                                        new Arg(new Node\Expr\ClassConstFetch(
                                            new Node\Name('\\' . BaseSchema::class),
                                            'class',
                                        )),
                                    ])),
                                ],
                            ),
                        ),
                        new Node\Stmt\Return_(
                            new Node\Expr\MethodCall(
                                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'hydrator'),
                                'hydrateObject',
                                [
                                    new Arg(new Node\Expr\ClassConstFetch(
                                        new Node\Name($payload->className->fullyQualified->source),
                                        'class',
                                    )),
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

        $class = $this->builderFactory->class($className->className)
            ->makeFinal()
            ->implement('\\' . WebHookInterface::class)
            ->addStmt(
                $this->builderFactory->method('__construct')->makePublic()->addParam(
                    $this->builderFactory->param('requestSchemaValidator')->makePrivate()->makeReadonly()->setType('\\' . SchemaValidator::class),
                )->addParam(
                    $this->builderFactory->param('hydrator')->makePrivate()->makeReadonly()->setType($webHookEvent->hydrator->className->fullyQualified->source),
                ),
            )
            ->addStmt($resolve);

        return new File(
            $package->destination->source,
            $className->relative,
            $this->builderFactory->namespace($className->namespace->source)->addStmt($class)->getNode(),
            File::DO_LOAD_ON_WRITE,
        );
    }
}
