<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\RootPackage;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit\SectionPackage;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Templates;
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
use OndraM\CiDetector\CiDetector;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;
use Safe\Exceptions\FilesystemException;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_pop;
use function array_unique;
use function array_unshift;
use function array_values;
use function assert;
use function count;
use function dirname;
use function file_exists;
use function getenv;
use function is_string;
use function ltrim;
use function md5;
use function realpath;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\mkdir;
use function Safe\unlink;
use function str_replace;
use function strlen;
use function strpos;
use function trim;
use function usleep;
use function WyriHaximus\Twig\render;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const PHP_EOL;

final readonly class Generator
{
    private bool $forceGeneration;
    private OpenApi $spec;
    private State $state;
    private string $currentSpecHash;

    private StatusOutput $statusOutput;

    public function __construct(
        private Configuration $configuration,
        string $configurationLocation,
    ) {
        $this->forceGeneration = is_string(getenv('FORCE_GENERATION')) && strlen(getenv('FORCE_GENERATION')) > 0;

        $this->statusOutput = new StatusOutput(
            ! (new CiDetector())->isCiDetected(),
            new Step('hash_current_spec', 'Hashing current spec', false),
            new Step('loading_state', 'Loading state', false),
            new Step('loading_spec', 'Loading spec', false),
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

        if (! $this->configuration->entryPoints->operations) {
            $this->statusOutput->markStepWontDo('generating_operationsinterface_entry_point');
            $this->statusOutput->markStepWontDo('generating_operations_entry_point');
        }

        if (! $this->configuration->entryPoints->webHooks) {
            $this->statusOutput->markStepWontDo('generating_webhooks');
            $this->statusOutput->markStepWontDo('generating_webhooks_entry_point');
        }

        if ($this->configuration->templates === null) {
            $this->statusOutput->markStepWontDo('generating_templated_files');
            $this->statusOutput->markStepWontDo('generating_templates_files_root_package');
            $this->statusOutput->markStepWontDo('generating_templates_files_common_package');
            $this->statusOutput->markStepWontDo('generating_templates_files_subsplit_package');
        }

        $specLocation = $this->configuration->spec;
        if (strpos($specLocation, '://') === false) {
            $specLocation = realpath($configurationLocation . $specLocation);
        }

        $this->statusOutput->markStepBusy('hash_current_spec');
        $this->currentSpecHash = md5(file_get_contents($specLocation));
        $this->statusOutput->markStepDone('hash_current_spec');

        $this->statusOutput->markStepBusy('loading_state');
        $this->state = (new ObjectMapperUsingReflection())->hydrateObject(
            State::class,
            /** @phpstan-ignore-next-line */
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
        $this->statusOutput->markStepDone('loading_state');

        if (
            ! $this->forceGeneration &&
            $this->state->specHash === $this->currentSpecHash &&
            (static function (string $root, Files $files, string ...$additionalFiles): bool {
                foreach ($additionalFiles as $additionalFile) {
                    if ($files->has($additionalFile) && (! file_exists($root . $additionalFile) || $files->get($additionalFile)->hash !== md5(file_get_contents($root . $additionalFile)))) {
                        return false;
                    }
                }

                return true;
            })($configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR, $this->state->additionalFiles, ...($this->configuration->state->additionalFiles ?? []))
        ) {
            throw new RuntimeException('Neither spec or marker files has changed so no need to regenerate');
        }

        $this->state->specHash = $this->currentSpecHash;

        $this->statusOutput->markStepBusy('loading_spec');
        $this->spec = Reader::readFromYamlFile($specLocation);
        $this->statusOutput->markStepDone('loading_spec');
    }

    public function generate(string $namespace, string $namespaceTest, string $configurationLocation): void
    {
        $existingFiles = array_map(
            static fn (StateFile $file): string => $file->name,
            $this->state->generatedFiles->files(),
        );
        $codePrinter   = new Standard();

        foreach ($this->all($configurationLocation) as $file) {
            $fileName = $configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $file->pathPrefix . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $file->fqcn) . '.php';
            if ($file->contents instanceof Node\Stmt\Namespace_) {
                array_unshift($file->contents->stmts, ...(static function (array $uses): iterable {
                    foreach ($uses as $use => $alias) {
                        yield new Node\Stmt\Use_([
                            new Node\Stmt\UseUse(
                                new Node\Name(
                                    $use,
                                ),
                                $alias,
                            ),
                        ]);
                    }
                })([
                    ltrim($namespace, '\\') . 'Error' => 'ErrorSchemas',
                    ltrim($namespace, '\\') . 'Hydrator' => null,
                    ltrim($namespace, '\\') . 'Operation' => null,
                    ltrim($namespace, '\\') . 'Operator' => null,
                    ltrim($namespace, '\\') . 'Schema' => null,
                    ltrim($namespace, '\\') . 'WebHook' => null,
                    ltrim($namespace, '\\') . 'Router' => null,
                    'League\OpenAPIValidation' => null,
                    'React\Http' => null,
                    'ApiClients\Contracts' => null,
                ]));
            }

            $fileContents     = (! is_string($file->contents) ? $codePrinter->prettyPrintFile([
                new Node\Stmt\Declare_([
                    new Node\Stmt\DeclareDeclare('strict_types', new Node\Scalar\LNumber(1)),
                ]),
                $file->contents,
            ]) : $file->contents) . PHP_EOL;
            $fileContentsHash = md5($fileContents);
            if (
                ! $this->state->generatedFiles->has($fileName) ||
                $this->state->generatedFiles->get($fileName)->hash !== $fileContentsHash ||
                $this->forceGeneration
            ) {
                try {
                    /** @phpstan-ignore-next-line */
                    @mkdir(dirname($fileName), 0744, true);
                } catch (FilesystemException) {
                    // @ignoreException
                }

                file_put_contents($fileName, $fileContents);
                $this->state->generatedFiles->upsert($fileName, $fileContentsHash);

                while (! file_exists($fileName) || $fileContentsHash !== md5(file_get_contents($fileName))) {
                    usleep(100);
                }
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

        try {
            /** @phpstan-ignore-next-line */
            @mkdir(dirname($configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR . $this->configuration->state->file), 0744, true);
        } catch (FilesystemException) {
            // @ignoreException
        }

        file_put_contents(
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
    private function all(string $configurationLocation): iterable
    {
        $schemaRegistry          = new SchemaRegistry(
            $this->configuration->namespace,
            $this->configuration->schemas->allowDuplication ?? false,
            $this->configuration->schemas->useAliasesForDuplication ?? false,
        );
        $schemas                 = [];
        $throwableSchemaRegistry = new ThrowableSchema();
        if (count($this->spec->components->schemas ?? []) > 0) {
            /** @phpstan-ignore-next-line */
            $this->statusOutput->itemForStep('gathering_schemas', count($this->spec->components->schemas));
            /** @phpstan-ignore-next-line */
            foreach ($this->spec->components->schemas as $name => $schema) {
                assert($schema instanceof \cebe\openapi\spec\Schema);
                $schemaRegistry->addClassName(Utils::className($name), $schema);
                $schemas[] = Gatherer\Schema::gather($this->configuration->namespace, Utils::className($name), $schema, $schemaRegistry);
                $this->statusOutput->advanceStep('gathering_schemas');
            }
        }

        $this->statusOutput->markStepDone('gathering_schemas');

        /**
         * @var array<class-string, array<Representation\WebHook>> $webHooks
         */
        $webHooks = [];
        if (count($this->spec->webhooks ?? []) > 0) {
            $this->statusOutput->itemForStep('gathering_webhooks', count($this->spec->webhooks));
            foreach ($this->spec->webhooks as $webHook) {
                try {
                    $webHookje = Gatherer\WebHook::gather($this->configuration->namespace, $webHook, $schemaRegistry);
                    if (! array_key_exists($webHookje->event, $webHooks)) {
                        $webHooks[$webHookje->event] = [];
                    }

                    $webHooks[$webHookje->event][] = $webHookje;
                    /** @phpstan-ignore-next-line */
                } catch (RuntimeException) {
                    // @ignoreException
                }

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

                $paths[] = Gatherer\Path::gather(
                    $this->configuration->namespace,
                    $pathClassName,
                    $path,
                    $pathItem,
                    $schemaRegistry,
                    $throwableSchemaRegistry,
                    $this->configuration->voter,
                );
                $this->statusOutput->advanceStep('gathering_paths');
            }

            $this->statusOutput->markStepDone('gathering_paths');
        }

        if ($this->configuration->subSplit === null) {
            $this->statusOutput->markStepWontDo(
                'client_subsplit',
                'generating_templates_files_root_package',
                'generating_templates_files_common_package',
                'generating_templates_files_subsplit_package',
                'generating_subsplit_configuration',
            );

            $this->statusOutput->markStepBusy('client_single');

            /** @phpstan-ignore-next-line */
            yield from $this->oneClient($configurationLocation, $schemaRegistry, $throwableSchemaRegistry, $schemas, $paths, $webHooks);

            $this->statusOutput->markStepDone('client_single');
        } else {
            $this->statusOutput->markStepWontDo(
                'client_single',
                'generating_templated_files',
            );

            $this->statusOutput->markStepBusy('client_subsplit');

            /** @phpstan-ignore-next-line */
            yield from $this->subSplitClient($configurationLocation, $schemaRegistry, $throwableSchemaRegistry, $schemas, $paths, $webHooks);

            $this->statusOutput->markStepDone('client_subsplit');
        }
    }

    /**
     * @param array<Representation\Schema>                       $schemas
     * @param array<Path>                                        $paths
     * @param array<class-string, array<Representation\WebHook>> $webHooks
     *
     * @return iterable<File>
     */
    private function oneClient(
        string $configurationLocation,
        SchemaRegistry $schemaRegistry,
        ThrowableSchema $throwableSchemaRegistry,
        array $schemas,
        array $paths,
        array $webHooks,
    ): iterable {
        $hydrators  = [];
        $operations = [];
        $this->statusOutput->itemForStep('generating_operations', count($paths));
        foreach ($paths as $path) {
            $hydrators[] = $path->hydrator;
            $operations  = [...$operations, ...$path->operations];
            foreach ($path->operations as $operation) {
                yield from Operation::generate(
                    $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );

                yield from Operator::generate(
                    $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );

                yield from OperationTest::generate(
                    $this->configuration->destination->test . DIRECTORY_SEPARATOR,
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
                $schemas[] = Gatherer\Schema::gather($this->configuration->namespace, $schema->className, $schema->schema, $schemaRegistry);
                $this->statusOutput->advanceStep('gathering_unknown_schemas');
            }
        }

        $this->statusOutput->markStepDone('gathering_unknown_schemas');

        $this->statusOutput->itemForStep('generating_schemas', count($schemas));
        foreach ($schemas as $schema) {
            yield from Schema::generate(
                $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                $schema,
                [...$schemaRegistry->aliasesForClassName($schema->className->relative)],
            );

            if ($throwableSchemaRegistry->has($schema->className->relative)) {
                yield from Error::generate(
                    $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                    $schema,
                );
            }

            $this->statusOutput->advanceStep('generating_schemas');
        }

        $this->statusOutput->markStepDone('generating_schemas');

        $client  = Gatherer\Client::gather($this->spec, ...$paths);
        $routers = new Client\Routers();

        $this->statusOutput->markStepBusy('generating_clientinterface');

        yield from ClientInterface::generate(
            $this->configuration,
            $this->configuration->destination->source . DIRECTORY_SEPARATOR,
            $operations,
        );

        $this->statusOutput->markStepDone('generating_clientinterface');

        $this->statusOutput->markStepBusy('generating_client');

        yield from Client::generate(
            $this->configuration,
            $this->configuration->destination->source . DIRECTORY_SEPARATOR,
            $client,
            $routers,
        );

        $this->statusOutput->markStepDone('generating_client');

        if ($this->configuration->entryPoints->operations) {
            $this->statusOutput->markStepBusy('generating_operationsinterface_entry_point');

            yield from OperationsInterface::generate(
                $this->configuration,
                $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                $operations,
            );

            $this->statusOutput->markStepDone('generating_operationsinterface_entry_point');

            $this->statusOutput->markStepBusy('generating_operations_entry_point');

            yield from Operations::generate(
                $this->configuration,
                $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                $paths,
                $operations,
            );

            $this->statusOutput->markStepDone('generating_operations_entry_point');
        }

        if ($this->configuration->entryPoints->webHooks) {
            $webHooksHydrators = [];
            $this->statusOutput->itemForStep('generating_webhooks', count($webHooks));
            foreach ($webHooks as $event => $webHook) {
                $webHooksHydrators[$event] = $hydrators[] = WebHookHydrator::gather(
                    $this->configuration->namespace,
                    $event,
                    ...$webHook,
                );

                yield from WebHook::generate(
                    $this->configuration->destination->source . DIRECTORY_SEPARATOR,
                    $this->configuration->namespace->source . '\\',
                    $event,
                    $schemaRegistry,
                    ...$webHook,
                );

                $this->statusOutput->advanceStep('generating_webhooks');
            }

            $this->statusOutput->markStepDone('generating_webhooks');

            $this->statusOutput->markStepBusy('generating_webhooks_entry_point');

            yield from WebHooks::generate($this->configuration->destination->source . DIRECTORY_SEPARATOR, $this->configuration->namespace->source . '\\', $webHooksHydrators, $webHooks);

            $this->statusOutput->markStepDone('generating_webhooks_entry_point');
        }

        $this->statusOutput->itemForStep('generating_hydrators', count($hydrators));
        foreach ($hydrators as $hydrator) {
            yield from Hydrator::generate($this->configuration->destination->source . DIRECTORY_SEPARATOR, $hydrator);

            $this->statusOutput->advanceStep('generating_hydrators');
        }

        $this->statusOutput->markStepDone('generating_hydrators');

        $this->statusOutput->markStepBusy('generating_hydrators_entry_point');

        yield from Hydrators::generate($this->configuration->destination->source . DIRECTORY_SEPARATOR, $this->configuration->namespace->source . '\\', ...$hydrators);

        $this->statusOutput->markStepDone('generating_hydrators_entry_point');

        if (! ($this->configuration->templates instanceof Templates)) {
            return;
        }

        $this->statusOutput->markStepBusy('generating_templated_files');
        \WyriHaximus\SubSplitTools\Files::setUp(
            $configurationLocation . $this->configuration->templates->dir,
            $configurationLocation . $this->configuration->destination->root . DIRECTORY_SEPARATOR,
            (static function (string $namespace, ?array $variables, array $operations, array $webHooks, Configuration $configuration): array {
                $vars              = $variables ?? [];
                $vars['namespace'] = $namespace;
                $vars['client']    = [
                    'configuration' => $configuration,
                    'operations' => $operations,
                    'webHooks' => $webHooks,
                ];

                return $vars;
            })(
                $this->configuration->namespace->source . '\\',
                $this->configuration->templates->variables,
                $operations,
                $webHooks,
                $this->configuration,
            ),
        );
        $this->statusOutput->markStepDone('generating_templated_files');
    }

    /**
     * @param array<Representation\Schema>                       $schemas
     * @param array<Path>                                        $paths
     * @param array<class-string, array<Representation\WebHook>> $webHooks
     *
     * @return iterable<File>
     */
    private function subSplitClient(
        string $configurationLocation,
        SchemaRegistry $schemaRegistry,
        ThrowableSchema $throwableSchemaRegistry,
        array $schemas,
        array $paths,
        array $webHooks,
    ): iterable {
        if ($this->configuration->subSplit === null) {
            throw new RuntimeException('Subsplit configuration must be present');
        }

        $splits = [];
        /**
         * @var array<string, array<Representation\Hydrator>> $hydrators
         */
        $hydrators  = [];
        $operations = [];
        $this->statusOutput->itemForStep('generating_operations', count($paths));
        foreach ($paths as $path) {
            $split = null;
            foreach ($this->configuration->subSplit->sectionGenerator ?? [] as $generator) {
                $split = $generator::path($path);
                if (is_string($split)) {
                    break;
                }
            }

            if (! is_string($split)) {
                continue;
            }

            $splits[]            = $split;
            $hydrators[$split][] = $path->hydrator;
            $operations          = [...$operations, ...$path->operations];
            foreach ($path->operations as $operation) {
                yield from Operation::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $split) . $this->configuration->destination->source,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );

                yield from Operator::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $split) . $this->configuration->destination->source,
                    $operation,
                    $path->hydrator,
                    $throwableSchemaRegistry,
                    $this->configuration,
                );

                yield from OperationTest::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $split) . $this->configuration->destination->test,
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
            $split = null;
            foreach ($this->configuration->subSplit->sectionGenerator ?? [] as $generator) {
                $split = $generator::webHook(...$webHook);
                if (is_string($split)) {
                    break;
                }
            }

            if (! is_string($split)) {
                continue;
            }

            $splits[]                  = $split;
            $webHooksHydrators[$event] = $hydrators[$split][] = WebHookHydrator::gather(
                $this->configuration->namespace,
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
                $schemas[] = Gatherer\Schema::gather($this->configuration->namespace, $schema->className, $schema->schema, $schemaRegistry);
                $this->statusOutput->advanceStep('gathering_unknown_schemas');
            }
        }

        $this->statusOutput->markStepDone('gathering_unknown_schemas');

        $sortedSchemas = [];
        foreach ($schemas as $schema) {
            if (array_key_exists($schema->className->relative, $sortedSchemas)) {
                continue;
            }

            $sortedSchemas[$schema->className->relative] = [
                'section' => 'common',
                'sections' => [],
            ];
        }

        foreach ($hydrators as $section => $sectionHydrators) {
            foreach ($sectionHydrators as $hydrator) {
                foreach ($hydrator->schemas as $schema) {
                    if ($throwableSchemaRegistry->has($schema->className->relative)) {
                        continue;
                    }

                    $sortedSchemas[$schema->className->relative]['sections'][] = $section;
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
            yield from Schema::generate(
                $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, 'common') . $this->configuration->destination->source,
                $schema,
                [...$schemaRegistry->aliasesForClassName($schema->className->relative)],
            );

            if ($throwableSchemaRegistry->has($schema->className->relative)) {
                yield from Error::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, 'common') . $this->configuration->destination->source,
                    $schema,
                );
            }

            $this->statusOutput->advanceStep('generating_schemas');
        }

        $this->statusOutput->markStepDone('generating_schemas');

        $client  = Gatherer\Client::gather($this->spec, ...$paths);
        $routers = new Client\Routers();

        $this->statusOutput->markStepBusy('generating_clientinterface');

        yield from ClientInterface::generate(
            $this->configuration,
            $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
            $operations,
        );

        $this->statusOutput->markStepDone('generating_clientinterface');

        $this->statusOutput->markStepBusy('generating_client');

        yield from Client::generate(
            $this->configuration,
            $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
            $client,
            $routers,
        );

        $this->statusOutput->markStepDone('generating_client');

        if ($this->configuration->entryPoints->operations) {
            $this->statusOutput->markStepBusy('generating_operationsinterface_entry_point');

            yield from OperationsInterface::generate(
                $this->configuration,
                $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
                $operations,
            );

            $this->statusOutput->markStepDone('generating_operationsinterface_entry_point');

            $this->statusOutput->markStepBusy('generating_operations_entry_point');

            yield from Operations::generate(
                $this->configuration,
                $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
                $paths,
                $operations,
            );

            $this->statusOutput->markStepDone('generating_operations_entry_point');
        }

        if ($this->configuration->entryPoints->webHooks) {
            $this->statusOutput->itemForStep('generating_webhooks', count($webHooks));
            foreach ($webHooks as $event => $webHook) {
                $split = null;
                foreach ($this->configuration->subSplit->sectionGenerator ?? [] as $generator) {
                    $split = $generator::webHook(...$webHook);
                    if (is_string($split)) {
                        break;
                    }
                }

                if (! is_string($split)) {
                    continue;
                }

                $splits[] = $split;

                yield from WebHook::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $split) . $this->configuration->destination->source,
                    $this->configuration->namespace->source,
                    $event,
                    $schemaRegistry,
                    ...$webHook,
                );

                $this->statusOutput->advanceStep('generating_webhooks');
            }

            $this->statusOutput->markStepDone('generating_webhooks');

            $this->statusOutput->markStepBusy('generating_webhooks_entry_point');

            yield from WebHooks::generate(
                $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
                $this->configuration->namespace->source,
                $webHooksHydrators,
                $webHooks
            );

            $this->statusOutput->markStepDone('generating_webhooks_entry_point');
        }

        $this->statusOutput->itemForStep('generating_hydrators', count($hydrators));
        foreach ($hydrators as $section => $sectionHydrators) {
            foreach ($sectionHydrators as $hydrator) {
                yield from Hydrator::generate(
                    $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->sectionPackage, $section) . $this->configuration->destination->source,
                    $hydrator
                );
            }

            $this->statusOutput->advanceStep('generating_hydrators');
        }

        $this->statusOutput->markStepDone('generating_hydrators');

        $this->statusOutput->markStepBusy('generating_hydrators_entry_point');

        yield from Hydrators::generate(
            $this->configuration->subSplit->subSplitsDestination . DIRECTORY_SEPARATOR . $this->splitPathPrefix($this->configuration->subSplit->rootPackage, '') . $this->configuration->destination->source,
            $this->configuration->namespace->source . '\\',
            ...(static function (array $hydratorSplit): iterable {
                foreach ($hydratorSplit as $hydrators) {
                    yield from [...$hydrators];
                }
            })($hydrators)
        );

        $this->statusOutput->markStepDone('generating_hydrators_entry_point');

        $subSplitConfig = [];
        $splits         = array_values(array_unique($splits));

        if ($this->configuration->templates instanceof Templates) {
            $this->statusOutput->markStepBusy('generating_templates_files_root_package');
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
                    'namespace' => $this->configuration->namespace->source,
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
            $this->statusOutput->markStepDone('generating_templates_files_root_package');

            $this->statusOutput->markStepBusy('generating_templates_files_common_package');
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
                    'namespace' => $this->configuration->namespace->source,
                    'requires-dev' => [
                        [
                            'name' => $this->configuration->subSplit->vendor . '/' . $this->configuration->subSplit->rootPackage->name,
                            'version' => $this->configuration->subSplit->targetVersion,
                        ],
                    ],
                ],
            );
            $this->statusOutput->markStepDone('generating_templates_files_common_package');

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
                        'namespace' => $this->configuration->namespace->source,
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
        }

        $this->statusOutput->markStepBusy('generating_subsplit_configuration');
        try {
            /** @phpstan-ignore-next-line */
            @mkdir(dirname($configurationLocation . $this->configuration->subSplit->subSplitConfiguration), 0744, true);
        } catch (FilesystemException) {
            // @ignoreException
        }

        file_put_contents(
            $configurationLocation . $this->configuration->subSplit->subSplitConfiguration,
            json_encode(
                [
                    'sub-splits' => array_values($subSplitConfig),
                ],
                JSON_PRETTY_PRINT,
            ) . PHP_EOL,
        );
        $this->statusOutput->markStepDone('generating_subsplit_configuration');
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
