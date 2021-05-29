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
            @mkdir(dirname($destinationPath . '/Schema/' . $schemaClassName), 0777, true);
            file_put_contents($destinationPath . '/Schema/' . $schemaClassName . '.php', $codePrinter->prettyPrintFile([
                    Schema::generate(
                        $name,
                        $this->cleanUpNamespace($namespace . dirname('Schema/' . $schemaClassName)),
                        strrev(explode('/', strrev($schemaClassName))[0]),
                        $schema,
                        $schemaClassNameMap
                    ),
                ]) . PHP_EOL);
        }

        foreach ($this->spec->paths as $path => $pathItem) {
            $pathClassName = $this->className($path);
            if (strlen($pathClassName) === 0) {
                continue;
            }
            @mkdir(dirname($destinationPath . '/Path/' . $pathClassName), 0777, true);
            file_put_contents($destinationPath . '/Path/' . $pathClassName . '.php', $codePrinter->prettyPrintFile([
                Path::generate(
                    $path,
                    $this->cleanUpNamespace($namespace . dirname('Path/' . $pathClassName)),
                    $namespace,
                    strrev(explode('/', strrev($pathClassName))[0]),
                    $pathItem
                ),
            ]) . PHP_EOL);
            foreach ($pathItem->getOperations() as $method => $operation) {
                $operationClassName = $this->className((new Convert($operation->operationId))->fromTrain()->toPascal());
                $operations[$method] = $operationClassName;
                if (strlen($operationClassName) === 0) {
                    continue;
                }
                @mkdir(dirname($destinationPath . '/Operation/' . $operationClassName), 0777, true);
                file_put_contents($destinationPath . '/Operation/' . $operationClassName . '.php', $codePrinter->prettyPrintFile([
                    Operation::generate(
                        $path,
                        $method,
                        $this->cleanUpNamespace($namespace . dirname('Operation/' . $operationClassName)),
                        strrev(explode('/', strrev($operationClassName))[0]),
                        $operation
                    ),
                ]) . PHP_EOL);
            }
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
}
