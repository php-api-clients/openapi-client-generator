<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Parameter;
use cebe\openapi\spec\Operation as openAPIOperation;
use cebe\openapi\spec\PathItem;
use Jawira\CaseConverter\Convert;
use Psr\Http\Message\ResponseInterface;

final class Operation
{
    public static function gather(
        string $className,
        string $method,
        string $path,
        openAPIOperation $operation,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation {
        $returnType = [];
        $parameters = [];
        $hasPerPageParameter = false;
        $hasPageParameter = false;
        foreach ($operation->parameters as $parameter) {
            if ($parameter->name === 'per_page') {
                $hasPerPageParameter = true;
            }
            if ($parameter->name === 'page') {
                $hasPageParameter = true;
            }
            $parameterType = str_replace([
                'integer',
                'any',
                'boolean',
            ], [
                'int',
                'mixed',
                'bool',
            ], implode('|', is_array($parameter->schema->type) ? $parameter->schema->type : [$parameter->schema->type]));

            $parameters[] = new Parameter(
                $parameter->name,
                (string)$parameter->description,
                $parameterType,
                $parameter->in,
                $parameter->schema->default,
            );
        }

        $classNameSanitized = str_replace('/', '\\', Utils::className($className));
        $requestBody = [];
        if ($operation->requestBody !== null) {
            foreach ($operation->requestBody->content as $contentType => $requestBodyDetails) {
                $requestBodyClassname = $schemaRegistry->get($requestBodyDetails->schema, $classNameSanitized . '\\Request\\' . Utils::className(str_replace('/', '', $contentType)));
                $requestBody[] = new OperationRequestBody(
                    $contentType,
                    Schema::gather($requestBodyClassname, $requestBodyDetails->schema, $schemaRegistry),
                );
            }
        }
        $response = [];
        foreach ($operation->responses as $code => $spec) {
            foreach ($spec->content as $contentType => $contentTypeMediaType) {
                $responseClassname = $schemaRegistry->get($contentTypeMediaType->schema, 'Operation\\' . $classNameSanitized . '\\Response\\' . Utils::className(str_replace('/', '', $contentType) . '\\H' . $code));
                $response[] = new OperationResponse(
                    $code,
                    $contentType,
                    $spec->description,
                    Schema::gather($responseClassname, $contentTypeMediaType->schema, $schemaRegistry),
                );
                $returnType[] = $responseClassname;
            }
        }

        if (count($returnType) === 0) {
            $returnType[] = '\\' . ResponseInterface::class;
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation(
            Utils::fixKeyword($className),
            $classNameSanitized,
            lcfirst(trim(Utils::basename($className),'\\')),
            trim(Utils::dirname($className),'\\'),
            $operation->operationId,
            strtoupper($method),
            $path,
            $hasPageParameter === true && $hasPerPageParameter === true, // This is very GitHub specific!!!
            array_unique($returnType),
            [
                ...array_filter($parameters, static fn (Parameter $parameter): bool => $parameter->default === null),
                ...array_filter($parameters, static fn (Parameter $parameter): bool => $parameter->default !== null),
            ],
            $requestBody,
            $response,
        );
    }
}
