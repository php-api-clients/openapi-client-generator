<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

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
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

final class Generator
{
    private OpenApi $spec;

    public function __construct(string $specUrl)
    {
        /** @var OpenApi spec */
        $this->spec = Reader::readFromYamlFile($specUrl);
    }

    public function generate(string $namespace, string $destinationPath, array $schemas, array $voters)
    {
        $existingFiles = iterator_to_array(Files::listExistingFiles($destinationPath . DIRECTORY_SEPARATOR));
        $namespace = Utils::cleanUpNamespace($namespace);
        $codePrinter = new Standard();

        foreach ($this->all($namespace, $destinationPath . DIRECTORY_SEPARATOR, $schemas, $voters) as $file) {
            $fileName = $destinationPath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($file->fqcn, strlen($namespace))) . '.php';
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
            include_once $fileName;
            if (array_key_exists($fileName, $existingFiles)) {
                unset($existingFiles[$fileName]);
            }
        }

        foreach ($existingFiles as $existingFile => $_) {
            unlink($existingFile);
        }
    }

    /**
     * @return iterable<File>
     */
    private function all(string $namespace, string $rootPath, array $schemas, array $voters): iterable
    {
        $schemaRegistry = new SchemaRegistry();
        if (array_key_exists('allowDuplication', $schemas)) {
            $schemaRegistry->setAllowDuplicatedSchemas($schemas['allowDuplication']);
        }
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

                $paths[] = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Path::gather($pathClassName, $path, $pathItem, $schemaRegistry, $voters);

            }
        }

        $hydrators = [];
        $operations = [];
        foreach ($paths as $path) {
            $hydrators[] = $path->hydrator;
            $operations = [...$operations, ...$path->operations];
            foreach ($path->operations as $operation) {
                yield from Operation::generate(
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
                $namespace,
                $schema,
            );
            if ($throwableSchemaRegistry->has($schema->className)) {
                yield from Error::generate(
                    $namespace,
                    $schema,
                );
            }
        }

        $client = \ApiClients\Tools\OpenApiClientGenerator\Gatherer\Client::gather($this->spec, ...$paths);

        yield from ClientInterface::generate(
            $namespace,
            $operations,
        );
        yield from Client::generate(
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
                $namespace,
                $event,
                $schemaRegistry,
                ...$webHook,
            );
        }

        yield from WebHooks::generate($namespace, $webHooksHydrators, $webHooks);

        foreach ($hydrators as $hydrator) {
            yield from Hydrator::generate($namespace, $hydrator);
        }

        yield from Hydrators::generate($namespace, ...$hydrators);
    }
}
