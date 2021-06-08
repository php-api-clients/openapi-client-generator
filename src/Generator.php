<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Path;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Schema;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHook;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHooks;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Jawira\CaseConverter\Convert;
use PhpParser\PrettyPrinter\Standard;

final class Generator
{
    private OpenApi $spec;

    public function __construct(string $specUrl)
    {
        /** @var OpenApi spec */
        $this->spec = Reader::readFromYamlFile($specUrl);
    }

    public function generate(string $namespace, string $destinationPath)
    {
        $namespace = $this->cleanUpNamespace($namespace);
        $codePrinter = new Standard();

        foreach ($this->all($namespace) as $file) {
            $fileName = $destinationPath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($file->fqcn(), strlen($namespace)));
            @mkdir(dirname($fileName), 0744, true);
            file_put_contents($fileName . '.php', $codePrinter->prettyPrintFile([$file->contents()]) . PHP_EOL);
        }
    }

    private function className(string $className): string
    {
        return str_replace(['{', '}', '-', '$', '_'], ['Cb', 'Rcb', 'Dash', '_', '\\'], (new Convert($className))->toPascal());
    }

    private function cleanUpNamespace(string $namespace): string
    {
        $namespace = str_replace('/', '\\', $namespace);
        $namespace = str_replace('\\\\', '\\', $namespace);

        return $namespace;
    }

    /**
     * @param string $namespace
     * @param string $destinationPath
     * @return iterable<File>
     */
    private function all(string $namespace): iterable
    {
        if (count($this->spec->components->schemas ?? []) > 0) {
            $schemaClassNameMap = [];
            foreach ($this->spec->components->schemas as $name => $schema) {
                $schemaClassName = $this->className($name);
                if (strlen($schemaClassName) === 0) {
                    continue;
                }
                $schemaClassNameMap[spl_object_hash($schema)] = $schemaClassName;
            }
            foreach ($this->spec->components->schemas as $name => $schema) {
                $schemaClassName = $schemaClassNameMap[spl_object_hash($schema)];
                if (strlen($schemaClassName) === 0) {
                    continue;
                }

                yield from Schema::generate(
                    $name,
                    $this->dirname($namespace . 'Schema/' . $schemaClassName),
                    $this->basename($namespace . 'Schema/' . $schemaClassName),
                    $schema,
                    $schemaClassNameMap,
                    $namespace . 'Schema'
                );
            }
        }

        if (count($this->spec->paths ?? []) > 0) {
            foreach ($this->spec->paths as $path => $pathItem) {
                $pathClassName = $this->className($path);
                if (strlen($pathClassName) === 0) {
                    continue;
                }

                yield from Path::generate(
                    $path,
                    $this->dirname($namespace . 'Path/' . $pathClassName),
                    $namespace,
                    $this->basename($namespace . 'Path/' . $pathClassName),
                    $pathItem
                );

                foreach ($pathItem->getOperations() as $method => $operation) {
                    $operationClassName = $this->className((new Convert($operation->operationId))->fromTrain()->toPascal());
                    $operations[$method] = $operationClassName;
                    if (strlen($operationClassName) === 0) {
                        continue;
                    }

                    yield from Operation::generate(
                        $path,
                        $method,
                        $this->dirname($namespace . 'Operation/' . $operationClassName),
                        $this->basename($namespace . 'Operation/' . $operationClassName),
                        $operation
                    );
                }
            }
        }

        if (count($this->spec->webhooks ?? []) > 0) {
            $pathClassNameMapping = [];
            foreach ($this->spec->webhooks as $path => $pathItem) {
                $webHookClassName = $this->className($path);
                $pathClassNameMapping[$path] = $this->fqcn($namespace . 'WebHook/' . $webHookClassName);
                if (strlen($webHookClassName) === 0) {
                    continue;
                }

                yield from WebHook::generate(
                    $path,
                    $this->dirname($namespace . 'WebHook/' . $webHookClassName),
                    $namespace,
                    $this->basename($namespace . 'WebHook/' . $webHookClassName),
                    $pathItem,
                    $schemaClassNameMap,
                    $namespace . 'WebHook'
                );
            }

            yield from WebHooks::generate(
                $this->dirname($namespace . 'WebHooks'),
                $pathClassNameMapping,
            );
        }
    }

    private function fqcn(string $fqcn): string
    {
        return str_replace('/', '\\', $fqcn);
    }

    private function dirname(string $fqcn): string
    {
        $fqcn = str_replace('\\', '/', $fqcn);

        return $this->cleanUpNamespace(dirname($fqcn));
    }

    private function basename(string $fqcn): string
    {
        $fqcn = str_replace('\\', '/', $fqcn);

        return $this->cleanUpNamespace(basename($fqcn));
    }
}
