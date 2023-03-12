<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Header;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRedirect;
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
        string $matchMethod,
        string $method,
        string $path,
        array $metaData,
        openAPIOperation $operation,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation {
        $returnType = [];
        $parameters = [];
        $redirects = [];
        foreach ($operation->parameters as $parameter) {
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
                    Schema::gather(
                        $responseClassname,
                        $contentTypeMediaType->schema,
                        $schemaRegistry,
                    ),
                );
                $returnType[] = $responseClassname;
            }
            if ($code >= 300 && $code < 400) {
                $headers = [];
                foreach ($spec->headers as $headerName => $headerSpec) {
                    $headers[$headerName] = new Header($headerName, Schema::gather(
                        $schemaRegistry->get(
                            $headerSpec->schema,
                            'WebHookHeader\\' . ucfirst(preg_replace('/\PL/u', '', $headerName)),
                        ),
                        $headerSpec->schema,
                        $schemaRegistry
                    ));
                }
                if (count($headers) > 0) {
                    $redirects[] = new OperationRedirect($code, $spec->description, $headers);
                }
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
            strtoupper($matchMethod),
            strtoupper($method),
            $path,
            $metaData,
            array_unique($returnType),
            [
                ...array_filter($parameters, static fn (Parameter $parameter): bool => $parameter->default === null),
                ...array_filter($parameters, static fn (Parameter $parameter): bool => $parameter->default !== null),
            ],
            $requestBody,
            $response,
            $redirects,
        );
    }
}
