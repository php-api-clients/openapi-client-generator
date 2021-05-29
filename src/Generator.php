<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Path;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Schema;
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
        return str_replace(['{', '}', '-', '$'], ['Cb', 'Rcb', 'Dash', '_'], (new Convert($className))->toPascal());
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
                    $this->cleanUpNamespace($namespace . dirname('Schema/' . $schemaClassName)),
                    strrev(explode('/', strrev($schemaClassName))[0]),
                    $schema,
                    $schemaClassNameMap
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
                    $this->cleanUpNamespace($namespace . dirname('Path/' . $pathClassName)),
                    $namespace,
                    strrev(explode('/', strrev($pathClassName))[0]),
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
                        $this->cleanUpNamespace($namespace . dirname('Operation/' . $operationClassName)),
                        strrev(explode('/', strrev($operationClassName))[0]),
                        $operation
                    );
                }
            }
        }
    }
}
