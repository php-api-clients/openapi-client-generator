<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Path;
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
        $codePrinter = new Standard();
        foreach ($this->spec->paths as $path => $pathItem) {
            $pathClassName = str_replace(['{', '}'], ['Cb', 'Rcb'], (new Convert($path))->toPascal()) . 'Path';
            @mkdir(dirname($destinationPath . '/Path' . $pathClassName), 0777, true);
            file_put_contents($destinationPath . '/Path/' . $pathClassName . '.php', $codePrinter->prettyPrintFile([
                Path::generate(
                    $path,
                    $namespace . str_replace('/', '\\', dirname('Path/' . $pathClassName)),
                    $namespace,
                    strrev(explode('/', strrev($pathClassName))[0]),
                    $pathItem
                ),
            ]) . PHP_EOL);
            foreach ($pathItem->getOperations() as $method => $operation) {
                $operationClassName = (new Convert($operation->operationId))->fromTrain()->toPascal() . 'Operation';
                $operations[$method] = $operationClassName;

                @mkdir(dirname($destinationPath . '/Operation/' . $operationClassName), 0777, true);
                file_put_contents($destinationPath . '/Operation/' . $operationClassName . '.php', $codePrinter->prettyPrintFile([
                    Operation::generate(
                        $path,
                        $method,
                        $namespace . str_replace('/', '\\', dirname('Operation/' . $operationClassName)),
                        strrev(explode('/', strrev($operationClassName))[0]),
                        $operation
                    ),
                ]) . PHP_EOL);
            }
        }

    }
}