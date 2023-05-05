<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\RootPackage;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\SectionPackage;
use ApiClients\Tools\OpenApiClientGenerator\Gatherer\WebHookHydrator;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client;
use ApiClients\Tools\OpenApiClientGenerator\Generator\ClientInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Error;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Hydrators;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Operations;
use ApiClients\Tools\OpenApiClientGenerator\Generator\OperationsInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\OperationTest;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Operator;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Schema;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHook;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHooks;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use ApiClients\Tools\OpenApiClientGenerator\State\File as StateFile;
use ApiClients\Tools\OpenApiClientGenerator\State\Files;
use ApiClients\Tools\OpenApiClientGenerator\StatusOutput\Step;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_pop;
use function array_unique;
use function array_unshift;
use function array_values;
use function count;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function is_string;
use function json_decode;
use function json_encode;
use function ltrim;
use function md5;
use function mkdir;
use function str_replace;
use function strlen;
use function trim;
use function unlink;
use function WyriHaximus\Twig\render;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const PHP_EOL;

final readonly class Generator
{
    private OpenApi $spec;
    private State $state;
    private string $currentSpecHash;

    private StatusOutput $statusOutput;

    public function __construct(
        private Configuration $configuration,
        string $configurationLocation,
    ) {
        $this->statusOutput = new StatusOutput(
            new Step('hash_current_spec', 'Hashing current spec', false),
            new Step('loading_state', 'Loading state', false),
            new Step('loading_spec', 'Loading spec', false),
            new Step('spec_loaded', 'Spec loaded', false),
            new Step('gathering_schemas', 'Gathering: Schemas', true),
            new Step('gathering_webhooks', 'Gathering: WebHooks', true),
            new Step('gathering_paths', 'Gathering: Paths', true),
            new Step('client_single', 'Client: Single package', false),
            new Step('client_subsplit', 'Client: SubSplit across multiple package', false),
            new Step('generating_operations', 'Generating: Operations', true),
            new Step('gathering_unknown_schemas', 'Gathering: Unknown Schemas', true),
            new Step('generating_schemas', 'Generating: Schemas', true),
            new Step('generating_clientinterface', 'Generating: ClientInterface', false),
            new Step('generating_client', 'Generating: Client', false),
            new Step('generating_operationsinterface_entry_point', 'Generating: OperationsInterface Entry Point', false),
            new Step('generating_operations_entry_point', 'Generating: Operations Entry Point', false),
            new Step('generating_webhooks', 'Generating: WebHooks', true),
            new Step('generating_webhooks_entry_point', 'Generating: WebHooks Entry Point', false),
            new Step('generating_hydrators', 'Generating: Hydrators', true),
            new Step('generating_hydrators_entry_point', 'Generating: Hydrators Entry Point', false),
            new Step('generating_templated_files', 'Generating: Templated files', false),
            new Step('generating_templates_files_root_package', 'Generating: Templates Files: Root Package', false),
            new Step('generating_templates_files_common_package', 'Generating: Templates Files: Common Package', false),
            new Step('generating_templates_files_subsplit_package', 'Generating: Templates Files: SubSplit Packages', true),
            new Step('generating_subsplit_configuration', 'Generating: SubSplit Configuration', false),
        );
        $this->statusOutput->render();

        $this->statusOutput->markStepDone('hash_current_spec');
        $this->currentSpecHash = md5(file_get_contents($this->configuration->spec));

        $this->statusOutput->markStepDone('loading_state');
        /** @var State */
        $this->state = (new ObjectMapperUsingReflection())->hydrateObject(
            State::class,
            file_exists($configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $this->configuration->state->file) ? json_decode(
                file_get_contents(
                    $configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $this->configuration->state->file,
                ),
                true
            ) : [
                'specHash' => '',
                'generatedFiles' => [
                    'files' => [],
                ],
                'additionalFiles' => [
                    'files' => [],
                ],
            ],
        );

        if (
            ! (getenv('FORCE_GENERATION') && strlen(getenv('FORCE_GENERATION')) > 0) &&
            $this->state->specHash !== null &&
            $this->state->specHash === $this->currentSpecHash &&
            (static function (string $root, Files $files, string ...$additionalFiles): bool {
                foreach ($additionalFiles as $additionalFile) {
                    if ($files->has($additionalFile) && (! file_exists($root . $additionalFile) || $files->get($additionalFile)->hash !== md5(file_get_contents($root . $additionalFile)))) {
                        return false;
                    }
                }

                return true;
            })($configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR, $this->state->additionalFiles, ...$this->configuration->state->additionalFiles)
        ) {
            throw new RuntimeException('Neither spec or marker files has changed so no need to regenerate');
        }

        $this->state->specHash = $this->currentSpecHash;

        $this->statusOutput->markStepDone('loading_spec');
        /** @var OpenApi */
        $this->spec = Reader::readFromYamlFile($this->configuration->spec);
        $this->statusOutput->markStepDone('spec_loaded');
    }

    public function generate(string $namespace, string $namespaceTest, string $configurationLocation): void
    {
        $existingFiles = array_map(
            static fn (StateFile $file): string => $file->name,
            $this->state->generatedFiles->files(),
        );
        $namespace     = Utils::cleanUpNamespace($namespace);
        $namespaceTest = Utils::cleanUpNamespace($namespaceTest);
        $codePrinter   = new Standard();

        foreach ($this->all($namespace, $namespaceTest, $configurationLocation) as $file) {
            $fileName = $configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $file->pathPrefix . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $file->fqcn) . '.php';
            if ($file->contents instanceof Node\Stmt\Namespace_) {
                array_unshift($file->contents->stmts, ...(static function (string $namespace, array $uses): iterable {
                    foreach ($uses as $use => $alias) {
                        yield new Node\Stmt\Use_([
                            new Node\Stmt\UseUse(
                                new Node\Name(
                                    ltrim($namespace, '\\') . $use,
                                ),
                                $alias,
                            ),
                        ]);
                    }
                })($namespace, [
                    'Error' => 'ErrorSchemas',
                    'Hydrator' => null,
                    'Operation' => null,
                    'Operator' => null,
                    'Schema' => null,
                    'WebHook' => null,
                    'Router' => null,
                ]));
            }

            $fileContents     = ($file->contents instanceof Node\Stmt\Namespace_ ? $codePrinter->prettyPrintFile([
                new Node\Stmt\Declare_([
                    new Node\Stmt\DeclareDeclare('strict_types', new Node\Scalar\LNumber(1)),
                ]),
                $file->contents,
            ]) : $file->contents) . PHP_EOL;
            $fileContentsHash = md5($fileContents);
            if (
                ! $this->state->generatedFiles->has($fileName) ||
                $this->state->generatedFiles->get($fileName)->hash !== $fileContentsHash
            ) {
                @mkdir(dirname($fileName), 0744, true);
                file_put_contents($fileName, $fileContents);
                $this->state->generatedFiles->upsert($fileName, $fileContentsHash);
            }

            include_once $fileName;
            $existingFiles = array_filter(
                $existingFiles,
                static fn (string $file): bool => $file !== $fileName,
            );
        }

        foreach ($existingFiles as $existingFile) {
            $this->state->generatedFiles->remove($existingFile);
            unlink($existingFile);
        }

        foreach ($this->state->additionalFiles->files() as $file) {
            $this->state->additionalFiles->remove($file->name);
        }

        foreach ($this->configuration->state->additionalFiles ?? [] as $additionalFile) {
            $this->state->additionalFiles->upsert(
                $additionalFile,
                file_exists($configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $additionalFile) ? md5(file_get_contents($configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $additionalFile)) : '',
            );
        }

        @mkdir(dirname($configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $this->configuration->state->file), 0744, true);
        \Safe\file_put_contents(
            $configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $this->configuration->state->file,
            json_encode(
                (new ObjectMapperUsingReflection())->serializeObject(
                    $this->state,
                ),
            ),
        );
    }

    /**
     * @return iterable<File>
     */
    private function all(string $namespace, string $namespaceTest, string $configurationLocation): iterable
    {
        $schemaRegistry          = new SchemaRegistry(
            $this->configuration->schemas !== null && $this->configuration->schemas->allowDuplication !== null ? $this->configuration->schemas->allowDuplication : false,
            $this->configuration->schemas !== null && $this->configuration->schemas->useAliasesForDuplication !== null ? $this->configuration->schemas->useAliasesForDuplication : false,
        );
        $schemas                 = [];
        $throwableSchemaRegistry = new ThrowableSchema();
        if (count($this->spec->components->schemas ?? []) > 0) {
            $this->statusOutput->itemForStep('gathering_schemas', count($this->spec->components->schemas));
            foreach ($this->spec->components->schemas as $name => $schema) {
                $schemaRegistry->addClassName(Utils::className($name), $schema);
                $schemas[] = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Schema::gather(Utils::className($name), $schema, $schemaRegistry);
                $this->statusOutput->advanceStep('gathering_schemas');
            }
        }
        $this->statusOutput->markStepDone('gathering_schemas');

        $webHooks = [];
        if (count($this->spec->webhooks ?? []) > 0) {
            $this->statusOutput->itemForStep('gathering_webhooks', count($this->spec->webhooks));
            foreach ($this->spec->webhooks as $webHook) {
                $webHookje = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\WebHook::gather($webHook, $schemaRegistry);
                if (! array_key_exists($webHookje->event, $webHooks)) {
                    $webHooks[$webHookje->event] = [];
                }

                $webHooks[$webHookje->event][] = $webHookje;
                $this->statusOutput->advanceStep('gathering_webhooks');
            }
        }
        $this->statusOutput->markStepDone('gathering_webhooks');

        $paths = [];
        if (count($this->spec->paths ?? []) > 0) {
            $this->statusOutput->itemForStep('gathering_paths', count($this->spec->paths));
            foreach ($this->spec->paths as $path => $pathItem) {
                if ($path === '/') {
                    $pathClassName = 'Root';
                } else {
                    $pathClassName = trim(Utils::className($path), '\\');
                }

                if (strlen($path) === 0 || strlen($pathClassName) === 0) {
                    continue;
                }

                $paths[]                       = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Path::gather($pathClassName, $path, $pathItem, $schemaRegistry, $this->configuration->voter);
                $webHooks[$webHookje->event][] = $webHookje;
                $this->statusOutput->advanceStep('gathering_paths');
            }

            $this->statusOutput->markStepDone('gathering_paths');
        }

        if ($this->configuration->subSplit === null) {
            $this->statusOutput->markStepDone('client_single');
            $this->statusOutput->markStepWontDo(
                'client_subsplit',
                'generating_templates_files_root_package',
                'generating_templates_files_common_package',
                'generating_templates_files_subsplit_package',
                'generating_subsplit_configuration',
            );

            yield from $this->oneClient($namespace, $namespaceTest, $configurationLocation, $schemaRegistry, $throwableSchemaRegistry, $schemas, $paths, $webHooks);
        } else {
            $this->statusOutput->markStepDone('client_subsplit');
            $this->statusOutput->markStepWontDo(
                'client_single',
                'generating_templated_files',
            );

            yield from $this->subSplitClient($namespace, $namespaceTest, $configurationLocation, $schemaRegistry, $throwableSchemaRegistry, $schemas, $paths, $webHooks);
        }
    }

    private function oneClient(string $namespace, string $namespaceTest, string $configurationLocation, SchemaRegistry $schemaRegistry, ThrowableSchema $throwableSchemaRegistry, array $schemas, array $paths, array $webHooks)
    {
        $hydrators  = [];
        $operations = [];
        $this->statusOutput->itemForStep('generating_operations', count($paths));
        foreach ($paths as $path) {
            $hydrators[] = $path->hydrator;
            $operations  = [...$operations, ...$path->operations];
            foreach ($path->operations as $operation) {
                yield from Operation::generate(
                    $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                    $namespace,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );

                yield from Operator::generate(
                    $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                    $namespace,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );

                yield from OperationTest::generate(
                    $this->configuration->destination->test . DIRECTORY_SEPARATOR,
                    $namespaceTest,
                    $namespace,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );
            }

            $this->statusOutput->advanceStep('generating_operations');
        }
        $this->statusOutput->markStepDone('generating_operations');

        $unknownSchemaCount = 0;
        while ($schemaRegistry->hasUnknownSchemas()) {
            $unknownSchemas      = [...$schemaRegistry->unknownSchemas()];
            $unknownSchemaCount += count($unknownSchemas);
            $this->statusOutput->itemForStep('gathering_unknown_schemas', $unknownSchemaCount);
            foreach ($unknownSchemas as $schema) {
                $schemas[] = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Schema::gather($schema['className'], $schema['schema'], $schemaRegistry);
                $this->statusOutput->advanceStep('gathering_unknown_schemas');
            }
        }
        $this->statusOutput->markStepDone('gathering_unknown_schemas');

        $this->statusOutput->itemForStep('generating_schemas', count($schemas));
        foreach ($schemas as $schema) {
            yield from Schema::generate(
                $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                $namespace,
                $schema,
                [...$schemaRegistry->aliasesForClassName($schema->className)],
            );

            if ($throwableSchemaRegistry->has($schema->className)) {
                yield from Error::generate(
                    $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                    $namespace,
                    $schema,
                );
            }

            $this->statusOutput->advanceStep('generating_schemas');
        }
        $this->statusOutput->markStepDone('generating_schemas');

        $client = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Client::gather($this->spec, ...$paths);
        $routers = new Client\Routers();

        $this->statusOutput->markStepDone('generating_clientinterface');
        yield from ClientInterface::generate(
            $this->configuration,
            $this->configuration->destination->source . DIRECTORY_SEPARATOR,
            $namespace,
            $operations,
        );

        $this->statusOutput->markStepDone('generating_client');
        yield from Client::generate(
            $this->configuration,
            $this->configuration->destination->source . DIRECTORY_SEPARATOR,
            $namespace,
            $client,
            $routers,
        );

        $this->statusOutput->markStepDone('generating_operationsinterface_entry_point');
        yield from OperationsInterface::generate(
            $this->configuration,
            $this->configuration->destination->source . DIRECTORY_SEPARATOR,
            $namespace,
            $operations,
        );

        $this->statusOutput->markStepDone('generating_operations_entry_point');
        yield from Operations::generate(
            $this->configuration,
            $this->configuration->destination->source . DIRECTORY_SEPARATOR,
            $namespace,
            $paths,
            $operations,
        );

        $webHooksHydrators = [];
        $this->statusOutput->itemForStep('generating_webhooks', count($webHooks));
        foreach ($webHooks as $event => $webHook) {
            $webHooksHydrators[$event] = $hydrators[] = WebHookHydrator::gather(
                $event,
                ...$webHook,
            );

            yield from WebHook::generate(
                $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                $namespace,
                $event,
                $schemaRegistry,
                ...$webHook,
            );

            $this->statusOutput->advanceStep('generating_webhooks');
        }
        $this->statusOutput->markStepDone('generating_webhooks');

        $this->statusOutput->markStepDone('generating_webhooks_entry_point');
        yield from WebHooks::generate($this->configuration->destination->source . DIRECTORY_SEPARATOR, $namespace, $webHooksHydrators, $webHooks);

        $this->statusOutput->itemForStep('generating_hydrators', count($hydrators));
        foreach ($hydrators as $hydrator) {
            yield from Hydrator::generate($this->configuration->destination->source . DIRECTORY_SEPARATOR, $namespace, $hydrator);

            $this->statusOutput->advanceStep('generating_hydrators');
        }
        $this->statusOutput->markStepDone('generating_hydrators');

        $this->statusOutput->markStepDone('generating_hydrators_entry_point');
        yield from Hydrators::generate($this->configuration->destination->source . DIRECTORY_SEPARATOR, $namespace, ...$hydrators);

        $this->statusOutput->markStepDone('generating_templated_files');
        \WyriHaximus\SubSplitTools\Files::setUp(
            $configurationLocation . $this->configuration->templates->dir,
            $configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR,
            (static function (string $namespace, ?array $variables): array {
                $vars              = $variables ?? [];
                $vars['namespace'] = $namespace;

                return $vars;
            })($namespace, $this->configuration->templates->variables),
        );
    }

    /**
     * @param array<Path>                                                            $paths
     * @param array<\ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook> $webHooks
     */
    private function subSplitClient(string $namespace, string $namespaceTest, string $configurationLocation, SchemaRegistry $schemaRegistry, ThrowableSchema $throwableSchemaRegistry, array $schemas, array $paths, array $webHooks)
    {
        $splits     = [];
        $hydrators  = [];
        $operations = [];
        $this->statusOutput->itemForStep('generating_operations', count($paths));
        foreach ($paths as $path) {
            foreach ($this->configuration->subSplit->sectionGenerator as $generator) {
                $split = $generator::path($path);
                if (is_string($split)) {
                    break;
                }
            }

            $splits[]            = $split;
            $hydrators[$split][] = $path->hydrator;
            $operations          = [...$operations, ...$path->operations];
            foreach ($path->operations as $operation) {
                yield from Operation::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $split) . $this->configuration->destination->source,
                    $namespace,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );

                yield from Operator::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $split) . $this->configuration->destination->source,
                    $namespace,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );

                yield from OperationTest::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $split) . $this->configuration->destination->test,
                    $namespaceTest,
                    $namespace,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );
            }

            $this->statusOutput->advanceStep('generating_operations');
        }
        $this->statusOutput->markStepDone('generating_operations');

        $webHooksHydrators = [];
        foreach ($webHooks as $event => $webHook) {
            foreach ($this->configuration->subSplit->sectionGenerator as $generator) {
                $split = $generator::webHook(...$webHook);
                if (is_string($split)) {
                    break;
                }
            }

            $splits[]                  = $split;
            $webHooksHydrators[$event] = $hydrators[$split][] = WebHookHydrator::gather(
                $event,
                ...$webHook,
            );
        }

        $unknownSchemaCount = 0;
        while ($schemaRegistry->hasUnknownSchemas()) {
            $unknownSchemas      = [...$schemaRegistry->unknownSchemas()];
            $unknownSchemaCount += count($unknownSchemas);
            $this->statusOutput->itemForStep('gathering_unknown_schemas', $unknownSchemaCount);
            foreach ($unknownSchemas as $schema) {
                $schemas[] = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Schema::gather($schema['className'], $schema['schema'], $schemaRegistry);
                $this->statusOutput->advanceStep('gathering_unknown_schemas');
            }
        }
        $this->statusOutput->markStepDone('gathering_unknown_schemas');

        $sortedSchemas = [];
        foreach ($schemas as $schema) {
            if (array_key_exists($schema->className, $sortedSchemas)) {
                continue;
            }

            $sortedSchemas[$schema->className] = [
                'section' => 'common',
                'sections' => [],
            ];
        }

        foreach ($hydrators as $section => $sectionHydrators) {
            foreach ($sectionHydrators as $hydrator) {
                foreach ($hydrator->schemas as $schema) {
                    if ($throwableSchemaRegistry->has($schema->className)) {
                        continue;
                    }

                    $sortedSchemas[$schema->className]['sections'][] = $section;
                }
            }
        }

        foreach ($sortedSchemas as $className => $sortedSchema) {
            $sortedSchemas[$className]['sections'] = array_values(array_unique($sortedSchemas[$className]['sections']));
            if (count($sortedSchemas[$className]['sections']) !== 1) {
                continue;
            }

            $sortedSchemas[$className]['section']  = array_pop($sortedSchemas[$className]['sections']);
            $sortedSchemas[$className]['sections'] = [
                $sortedSchemas[$className]['section'],
            ];
        }

        $this->statusOutput->itemForStep('generating_schemas', count($schemas));
        foreach ($schemas as $schema) {
            if ($throwableSchemaRegistry->has($schema->className)) {
                yield from Schema::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, 'common') . $this->configuration->destination->source,
                    $namespace,
                    $schema,
                    [...$schemaRegistry->aliasesForClassName($schema->className)],
                );

                yield from Error::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, 'common') . $this->configuration->destination->source,
                    $namespace,
                    $schema,
                );
            } else {
                $aliases = [...$schemaRegistry->aliasesForClassName($schema->className)];

                yield from Schema::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, count($aliases) > 0 ? 'common' : $sortedSchemas[$schema->className]['section']) . $this->configuration->destination->source,
                    $namespace,
                    $schema,
                    $aliases,
                );
            }

            $this->statusOutput->advanceStep('generating_schemas');
        }
        $this->statusOutput->markStepDone('generating_schemas');

        $client = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Client::gather($this->spec, ...$paths);
        $routers = new Client\Routers();

        $this->statusOutput->markStepDone('generating_clientinterface');
        yield from ClientInterface::generate(
            $this->configuration,
            $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
            $namespace,
            $operations,
        );

        $this->statusOutput->markStepDone('generating_client');
        yield from Client::generate(
            $this->configuration,
            $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
            $namespace,
            $client,
            $routers,
        );

        $this->statusOutput->markStepDone('generating_operationsinterface_entry_point');
        yield from OperationsInterface::generate(
            $this->configuration,
            $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
            $namespace,
            $operations,
        );

        $this->statusOutput->markStepDone('generating_operations_entry_point');
        yield from Operations::generate(
            $this->configuration,
            $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
            $namespace,
            $paths,
            $operations,
        );

        $this->statusOutput->itemForStep('generating_webhooks', count($webHooks));
        foreach ($webHooks as $event => $webHook) {
            foreach ($this->configuration->subSplit->sectionGenerator as $generator) {
                $split = $generator::webHook(...$webHook);
                if (is_string($split)) {
                    break;
                }
            }

            $splits[] = $split;

            yield from WebHook::generate(
                $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $split) . $this->configuration->destination->source,
                $namespace,
                $event,
                $schemaRegistry,
                ...$webHook,
            );

            $this->statusOutput->advanceStep('generating_webhooks');
        }
        $this->statusOutput->markStepDone('generating_webhooks');

        $this->statusOutput->markStepDone('generating_webhooks_entry_point');
        yield from WebHooks::generate(
            $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
            $namespace,
            $webHooksHydrators,
            $webHooks
        );

        $this->statusOutput->itemForStep('generating_hydrators', count($hydrators));
        foreach ($hydrators as $section => $sectionHydrators) {
            foreach ($sectionHydrators as $hydrator) {
                yield from Hydrator::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $section) . $this->configuration->destination->source,
                    $namespace,
                    $hydrator
                );
            }

            $this->statusOutput->advanceStep('generating_hydrators');
        }
        $this->statusOutput->markStepDone('generating_hydrators');

        $this->statusOutput->markStepDone('generating_hydrators_entry_point');
        yield from Hydrators::generate(
            $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
            $namespace,
            ...(static function (array $hydratorSplit): iterable {
                foreach ($hydratorSplit as $hydrators) {
                    yield from [...$hydrators];
                }
            })($hydrators)
        );

        $subSplitConfig = [];
        $splits         = array_values(array_filter(array_unique($splits), static fn ($stringOrFalse): bool => is_string($stringOrFalse)));

        $this->statusOutput->markStepDone('generating_templates_files_root_package');
        $subSplitConfig['root'] = [
            'name' => $this->packageName($this->configuration->subSplit->rootPackage->name, ''),
            'directory' => $this->packageName($this->configuration->subSplit->rootPackage->name, ''),
            'target' => 'git@github.com:php-api-clients/' . $this->packageName($this->configuration->subSplit->rootPackage->name, '') . '.git',
            'target-branch' => $this->configuration->subSplit->branch,
        ];
        \WyriHaximus\SubSplitTools\Files::setUp(
            $configurationLocation . $this->configuration->templates->dir,
            $configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->packageName($this->configuration->subSplit->rootPackage->name, ''),
            [
                'packageName' => $this->configuration->subSplit->rootPackage->name,
                'fullName' => render($this->configuration->subSplit->fullName, ['section' => '']),
                'namespace' => $namespace,
                'requires' => [
                    [
                        'name' => $this->configuration->subSplit->vendor . '/' . $this->packageName($this->configuration->subSplit->sectionPackage->name, 'common'),
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
                    })($this->configuration->subSplit->sectionPackage->name, ...$splits),
                ],
            ],
        );

        $this->statusOutput->markStepDone('generating_templates_files_common_package');
        $subSplitConfig['common'] = [
            'name' => $this->packageName($this->configuration->subSplit->sectionPackage->name, 'common'),
            'directory' => $this->packageName($this->configuration->subSplit->sectionPackage->name, 'common'),
            'target' => 'git@github.com:php-api-clients/' . $this->packageName($this->configuration->subSplit->sectionPackage->name, 'common') . '.git',
            'target-branch' => $this->configuration->subSplit->branch,
        ];
        \WyriHaximus\SubSplitTools\Files::setUp(
            $configurationLocation . $this->configuration->templates->dir,
            $configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->packageName($this->configuration->subSplit->sectionPackage->name, 'common'),
            [
                'packageName' => $this->packageName($this->configuration->subSplit->sectionPackage->name, 'common'),
                'fullName' => render($this->configuration->subSplit->fullName, ['section' => 'common']),
                'namespace' => $namespace,
                'requires-dev' => [
                    [
                        'name' => $this->configuration->subSplit->vendor . '/' . $this->configuration->subSplit->rootPackage->name,
                        'version' => $this->configuration->subSplit->targetVersion,
                    ],
                ],
            ],
        );

        $this->statusOutput->itemForStep('generating_templates_files_subsplit_package', count($splits));
        foreach ($splits as $split) {
            $subSplitConfig[$split] = [
                'name' => $this->packageName($this->configuration->subSplit->sectionPackage->name, $split),
                'directory' => $this->packageName($this->configuration->subSplit->sectionPackage->name, $split),
                'target' => 'git@github.com:php-api-clients/' . $this->packageName($this->configuration->subSplit->sectionPackage->name, $split) . '.git',
                'target-branch' => $this->configuration->subSplit->branch,
            ];
            \WyriHaximus\SubSplitTools\Files::setUp(
                $configurationLocation . $this->configuration->templates->dir,
                $configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->packageName($this->configuration->subSplit->sectionPackage->name, $split),
                [
                    'packageName' => $this->packageName($this->configuration->subSplit->sectionPackage->name, $split),
                    'fullName' => render($this->configuration->subSplit->fullName, ['section' => $split]),
                    'namespace' => $namespace,
                    'requires' => [
                        [
                            'name' => $this->configuration->subSplit->vendor . '/' . $this->packageName($this->configuration->subSplit->sectionPackage->name, 'common'),
                            'version' => $this->configuration->subSplit->targetVersion,
                        ],
                    ],
                    'requires-dev' => [
                        [
                            'name' => $this->configuration->subSplit->vendor . '/' . $this->configuration->subSplit->rootPackage->name,
                            'version' => $this->configuration->subSplit->targetVersion,
                        ],
                    ],
                ],
            );
            $this->statusOutput->advanceStep('generating_templates_files_subsplit_package');
        }
        $this->statusOutput->markStepDone('generating_templates_files_subsplit_package');

        $this->statusOutput->markStepDone('generating_subsplit_configuration');
        @mkdir(dirname($configurationLocation . $this->configuration->subSplit->subSplitConfiguration), 0744, true);
        file_put_contents(
            $configurationLocation . $this->configuration->subSplit->subSplitConfiguration,
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
            ['section' => $split],
        );
    }

    private function splitPathPrefix(RootPackage|SectionPackage $package, string $section): string
    {
        return $this->packageName($package->name, $section) . '/';
    }
}
