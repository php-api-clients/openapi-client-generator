<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\RootPackage;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\SectionPackage;
use ApiClients\Tools\OpenApiClientGenerator\Gatherer\OperationHydrator;
use ApiClients\Tools\OpenApiClientGenerator\Gatherer\WebHookHydrator;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client;
use ApiClients\Tools\OpenApiClientGenerator\Generator\ClientInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Error;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Hydrators;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Schema;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHook;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHooks;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use function WyriHaximus\Twig\render;

final class Generator
{
    private OpenApi $spec;

    public function __construct(string $specUrl)
    {
        /** @var OpenApi spec */
        $this->spec = Reader::readFromYamlFile($specUrl);
    }

    public function generate(string $namespace, string $clientRoot, Configuration $configuration)
    {
        $existingFiles = iterator_to_array(Files::listExistingFiles($clientRoot . $configuration->destination->source . DIRECTORY_SEPARATOR));
        $namespace = Utils::cleanUpNamespace($namespace);
        $codePrinter = new Standard();

        foreach ($this->all($namespace, $clientRoot, $configuration) as $file) {
            $fileName = $clientRoot . $configuration->destination->source . DIRECTORY_SEPARATOR . $file->pathPrefix . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($file->fqcn, strlen($namespace))) . '.php';
            if ($file->contents instanceof Node\Stmt\Namespace_) {
                array_unshift($file->contents->stmts, ...[
                    new Node\Stmt\Use_([
                        new Node\Stmt\UseUse(
                            new Node\Name(
                                ltrim($namespace, '\\') . 'Error',
                            ),
                            'ErrorSchemas'
                        )
                    ]),
                    new Node\Stmt\Use_([
                        new Node\Stmt\UseUse(
                            new Node\Name(
                                ltrim($namespace, '\\') . 'Hydrator',
                            )
                        )
                    ]),
                    new Node\Stmt\Use_([
                        new Node\Stmt\UseUse(
                            new Node\Name(
                                ltrim($namespace, '\\') . 'Operation',
                            )
                        )
                    ]),
                    new Node\Stmt\Use_([
                        new Node\Stmt\UseUse(
                            new Node\Name(
                                ltrim($namespace, '\\') . 'Schema',
                            )
                        )
                    ]),
                    new Node\Stmt\Use_([
                        new Node\Stmt\UseUse(
                            new Node\Name(
                                ltrim($namespace, '\\') . 'WebHook',
                            )
                        )
                    ]),
                ]);
            }
            $fileContents = ($file->contents instanceof Node\Stmt\Namespace_ ? $codePrinter->prettyPrintFile([
                new Node\Stmt\Declare_([
                    new Node\Stmt\DeclareDeclare('strict_types', new Node\Scalar\LNumber(1)),
                ]),
                $file->contents,
            ]) : $file->contents) . PHP_EOL;
            if ((array_key_exists($fileName, $existingFiles) && md5($fileContents) !== $existingFiles[$fileName]) || !array_key_exists($fileName, $existingFiles)) {
                @mkdir(dirname($fileName), 0744, true);
                file_put_contents($fileName, $fileContents);
            }
//            echo $fileName, PHP_EOL;
            include_once $fileName;
//            if (array_key_exists($fileName, $existingFiles)) {
//                unset($existingFiles[$fileName]);
//            }
        }

//        foreach ($existingFiles as $existingFile => $_) {
//            unlink($existingFile);
//        }
    }

    /**
     * @return iterable<File>
     */
    private function all(string $namespace, string $clientRoot, Configuration $configuration): iterable
    {
        $schemaRegistry = new SchemaRegistry(
            $configuration->schemas !== null && $configuration->schemas->allowDuplication !== null ? $configuration->schemas->allowDuplication : false,
            $configuration->schemas !== null && $configuration->schemas->useAliasesForDuplication !== null ? $configuration->schemas->useAliasesForDuplication : false,
        );
        $schemas = [];
        $throwableSchemaRegistry = new ThrowableSchema();
        if (count($this->spec->components->schemas ?? []) > 0) {
            foreach ($this->spec->components->schemas as $name => $schema) {
                $schemaRegistry->addClassName(Utils::className($name), $schema);
                $schemas[] = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Schema::gather(Utils::className($name), $schema, $schemaRegistry);
            }
        }

        $webHooks = [];
        if (count($this->spec->webhooks ?? []) > 0) {
            foreach ($this->spec->webhooks as $webHook) {
                $webHookje = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\WebHook::gather($webHook, $schemaRegistry);
                if (!array_key_exists($webHookje->event, $webHooks)) {
                    $webHooks[$webHookje->event] = [];
                }
                $webHooks[$webHookje->event][] = $webHookje;
            }
        }

        $paths = [];
        if (count($this->spec->paths ?? []) > 0) {
            foreach ($this->spec->paths as $path => $pathItem) {
                if ($path === '/') {
                    $pathClassName = 'Root';
                } else {
                    $pathClassName = trim(Utils::className($path), '\\');
                }

                if (strlen($path) === 0 || strlen($pathClassName) === 0) {
                    continue;
                }

                $paths[] = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Path::gather($pathClassName, $path, $pathItem, $schemaRegistry, $configuration->voter);

            }
        }

        if ($configuration->subSplit === null) {
            yield from $this->oneClient($namespace, $schemaRegistry, $throwableSchemaRegistry, $schemas, $paths, $webHooks);
        } else {
            yield from $this->subSplitClient($namespace, $clientRoot, $configuration, $schemaRegistry, $throwableSchemaRegistry, $schemas, $paths, $webHooks);
        }
    }

    private function oneClient(string $namespace, SchemaRegistry $schemaRegistry, ThrowableSchema $throwableSchemaRegistry, array $schemas, array $paths, array $webHooks)
    {
        $hydrators = [];
        $operations = [];
        foreach ($paths as $path) {
            $hydrators[] = $path->hydrator;
            $operations = [...$operations, ...$path->operations];
            foreach ($path->operations as $operation) {
                yield from Operation::generate(
                    '',
                    $namespace,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                );
            }
        }

        while ($schemaRegistry->hasUnknownSchemas()) {
            foreach ($schemaRegistry->unknownSchemas() as $schema) {
                $schemas[] = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Schema::gather($schema['className'], $schema['schema'], $schemaRegistry);
            }
        }

        foreach ($schemas as $schema) {
            yield from Schema::generate(
                '',
                $namespace,
                $schema,
                [...$schemaRegistry->aliasesForClassName($schema->className)],
            );
            if ($throwableSchemaRegistry->has($schema->className)) {
                yield from Error::generate(
                    '',
                    $namespace,
                    $schema,
                );
            }
        }

        $client = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Client::gather($this->spec, ...$paths);

        yield from ClientInterface::generate(
            '',
            $namespace,
            $operations,
        );
        yield from Client::generate(
            '',
            $namespace,
            $client,
        );

        $webHooksHydrators = [];
        foreach ($webHooks as $event => $webHook) {
            $webHooksHydrators[$event] = $hydrators[] = WebHookHydrator::gather(
                $event,
                ...$webHook,
            );
            yield from WebHook::generate(
                '',
                $namespace,
                $event,
                $schemaRegistry,
                ...$webHook,
            );
        }

        yield from WebHooks::generate('', $namespace, $webHooksHydrators, $webHooks);

        foreach ($hydrators as $hydrator) {
            yield from Hydrator::generate('', $namespace, $hydrator);
        }

        yield from Hydrators::generate('', $namespace, ...$hydrators);
    }

    /**
     * @param array<Path> $paths
     * @param array<\ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook> $webHooks
     */
    private function subSplitClient(string $namespace, string $clientRoot, Configuration $configuration, SchemaRegistry $schemaRegistry, ThrowableSchema $throwableSchemaRegistry, array $schemas, array $paths, array $webHooks)
    {
        $splits = [];
        $hydrators = [];
        $operations = [];
        foreach ($paths as $path) {
            foreach ($configuration->subSplit->sectionGenerator as $generator) {
                $split = $generator::path($path);
                if (is_string($split)) {
                    break;
                }
            }
            $splits[] = $split;
            $hydrators[$split][] = $path->hydrator;
            $operations = [...$operations, ...$path->operations];
            foreach ($path->operations as $operation) {
                yield from Operation::generate(
                    $this->splitPathPrefix($configuration->subSplit->sectionPackage, $split),
                    $namespace,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                );
            }
        }

        $webHooksHydrators = [];
        foreach ($webHooks as $event => $webHook) {
            foreach ($configuration->subSplit->sectionGenerator as $generator) {
                $split = $generator::webHook(...$webHook);
                if (is_string($split)) {
                    break;
                }
            }
            $splits[] = $split;
            $webHooksHydrators[$event] = $hydrators[$split][] = WebHookHydrator::gather(
                $event,
                ...$webHook,
            );
        }

        while ($schemaRegistry->hasUnknownSchemas()) {
            foreach ($schemaRegistry->unknownSchemas() as $schema) {
                $schemas[] = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Schema::gather($schema['className'], $schema['schema'], $schemaRegistry);
            }
        }

        $sortedSchemas = [];
        foreach ($schemas as $schema) {
            if (!array_key_exists($schema->className, $sortedSchemas)) {
                $sortedSchemas[$schema->className] = [
                    'section' => 'common',
                    'sections' => [],
                ];
            }
        }

        foreach ($hydrators as $section => $sectionHydrators) {
            foreach ($sectionHydrators as $hydrator) {
                foreach ($hydrator->schemas as $schema) {
                    if (!$throwableSchemaRegistry->has($schema->className)) {
                        $sortedSchemas[$schema->className]['sections'][] = $section;
                    }
                }
            }
        }

        foreach ($sortedSchemas as $className => $sortedSchema) {
            $sortedSchemas[$className]['sections'] = array_values(array_unique($sortedSchemas[$className]['sections']));
            if (count($sortedSchemas[$className]['sections']) === 1) {
                $sortedSchemas[$className]['section'] = array_pop($sortedSchemas[$className]['sections']);
                $sortedSchemas[$className]['sections'] = [
                    $sortedSchemas[$className]['section'],
                ];
            }
        }

        foreach ($schemas as $schema) {
            if ($throwableSchemaRegistry->has($schema->className)) {
                yield from Schema::generate(
                    $this->splitPathPrefix($configuration->subSplit->sectionPackage, 'common'),
                    $namespace,
                    $schema,
                    [...$schemaRegistry->aliasesForClassName($schema->className)],
                );
                yield from Error::generate(
                    $this->splitPathPrefix($configuration->subSplit->sectionPackage, 'common'),
                    $namespace,
                    $schema,
                );
            } else {
                $aliases = [...$schemaRegistry->aliasesForClassName($schema->className)];
                yield from Schema::generate(
                    $this->splitPathPrefix($configuration->subSplit->sectionPackage, count($aliases) > 0 ? 'common' : $sortedSchemas[$schema->className]['section']),
                    $namespace,
                    $schema,
                    $aliases,
                );
            }
        }

        $client = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Client::gather($this->spec, ...$paths);

        yield from ClientInterface::generate(
            $this->splitPathPrefix($configuration->subSplit->rootPackage, ''),
            $namespace,
            $operations,
        );
        yield from Client::generate(
            $this->splitPathPrefix($configuration->subSplit->rootPackage, ''),
            $namespace,
            $client,
        );

        foreach ($webHooks as $event => $webHook) {
            foreach ($configuration->subSplit->sectionGenerator as $generator) {
                $split = $generator::webHook(...$webHook);
                if (is_string($split)) {
                    break;
                }
            }
            $splits[] = $split;
            yield from WebHook::generate(
                $this->splitPathPrefix($configuration->subSplit->sectionPackage, $split),
                $namespace,
                $event,
                $schemaRegistry,
                ...$webHook,
            );
        }

        yield from WebHooks::generate(
            $this->splitPathPrefix($configuration->subSplit->rootPackage, ''),
            $namespace,
            $webHooksHydrators,
            $webHooks
        );

        foreach ($hydrators as $section => $sectionHydrators) {
            foreach ($sectionHydrators as $hydrator) {
                yield from Hydrator::generate(
                    $this->splitPathPrefix($configuration->subSplit->sectionPackage, $section),
                    $namespace,
                    $hydrator
                );
            }
        }

        yield from Hydrators::generate(
            $this->splitPathPrefix($configuration->subSplit->rootPackage, ''),
            $namespace,
            ...(static function (array $hydratorSplit): iterable {
                foreach ($hydratorSplit as $hydrators) {
                    yield from [...$hydrators];
                }
            })($hydrators)
        );

        $subSplitConfig = [];
        $splits = array_values(array_filter(array_unique($splits), static fn ($stringOrFalse): bool => is_string($stringOrFalse)));

        $subSplitConfig['root'] = [
            'name' => $this->packageName($configuration->subSplit->rootPackage->name, ''),
            'directory' => $this->packageName($configuration->subSplit->rootPackage->name, ''),
            'target' => 'git@github.com:php-api-clients/' . $this->packageName($configuration->subSplit->rootPackage->name, '') . '.git',
            'target-branch' => $configuration->subSplit->branch,
        ];
        \WyriHaximus\SubSplitTools\Files::setUp(
            $clientRoot . $configuration->subSplit->templatesDir,
            $clientRoot . $configuration->destination->source . DIRECTORY_SEPARATOR . $this->packageName($configuration->subSplit->rootPackage->name, ''),
            [
                'packageName' => $configuration->subSplit->rootPackage->name,
                'fullName' => render($configuration->subSplit->fullName, ['section' => '',]),
                'namespace' => $namespace,
                'requires' => [
                    [
                        'name' => $this->packageName($configuration->subSplit->sectionPackage->name, 'common'),
                        'version' => '^0.3',
                    ],
                ],
                'suggests' => [
                    ...(function (string $sectionPackageName, string ...$splits): iterable {
                        foreach ($splits as $split) {
                            yield [
                                'name' => $this->packageName($sectionPackageName, $split),
                                'reason' => '*',
                            ];
                        }
                    })($configuration->subSplit->sectionPackage->name, ...$splits)
                ],
            ],
        );

        $subSplitConfig['common'] = [
            'name' => $this->packageName($configuration->subSplit->sectionPackage->name, 'common'),
            'directory' => $this->packageName($configuration->subSplit->sectionPackage->name, 'common'),
            'target' => 'git@github.com:php-api-clients/' . $this->packageName($configuration->subSplit->sectionPackage->name, 'common') . '.git',
            'target-branch' => $configuration->subSplit->branch,
        ];
        \WyriHaximus\SubSplitTools\Files::setUp(
            $clientRoot . $configuration->subSplit->templatesDir,
            $clientRoot . $configuration->destination->source . DIRECTORY_SEPARATOR . $this->packageName($configuration->subSplit->sectionPackage->name, 'common'),
            [
                'packageName' => $this->packageName($configuration->subSplit->sectionPackage->name, 'common'),
                'fullName' => render($configuration->subSplit->fullName, ['section' => 'common']),
                'namespace' => $namespace,
            ],
        );

        foreach ($splits as $split) {
            $subSplitConfig[$split] = [
                'name' => $this->packageName($configuration->subSplit->sectionPackage->name, $split),
                'directory' => $this->packageName($configuration->subSplit->sectionPackage->name, $split),
                'target' => 'git@github.com:php-api-clients/' . $this->packageName($configuration->subSplit->sectionPackage->name, $split) . '.git',
                'target-branch' => $configuration->subSplit->branch,
            ];
            \WyriHaximus\SubSplitTools\Files::setUp(
                $clientRoot . $configuration->subSplit->templatesDir,
                $clientRoot . $configuration->destination->source . DIRECTORY_SEPARATOR . $this->packageName($configuration->subSplit->sectionPackage->name, $split),
                [
                    'packageName' => $this->packageName($configuration->subSplit->sectionPackage->name, $split),
                    'fullName' => render($configuration->subSplit->fullName, ['section' => $split]),
                    'namespace' => $namespace,
                    'requires' => [
                        [
                            'name' => $this->packageName($configuration->subSplit->sectionPackage->name, 'common'),
                            'version' => '^0.3',
                        ],
                    ],
                ],
            );
        }

        @mkdir(dirname($clientRoot . $configuration->subSplit->subSplitConfiguration), 0744, true);
        file_put_contents(
            $clientRoot . $configuration->subSplit->subSplitConfiguration,
            json_encode(
                [
                    'sub-splits' => array_values($subSplitConfig),
                ],
                JSON_PRETTY_PRINT,
            ) . PHP_EOL,
        );
    }

    private function packageName(string $name, string $split): string
    {
        return render(
            $name,
            [
                'section' => $split,
            ],
        );
    }

    private function splitPathPrefix(RootPackage|SectionPackage $package, string $section): string
    {
        return $this->packageName($package->name, $section) . '/src';
    }
}
