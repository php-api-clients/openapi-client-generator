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
use PhpParser\Node;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;

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

            [$example, $exampleNode] = (static function (string $type, ?string $format): array {
                if ($type === 'int' || $type === '?int') {
                    return [13, new Node\Scalar\LNumber(13)];
                } elseif ($type === 'float' || $type === '?float' || $type === 'int|float' || $type === 'null|int}float') {
                    return [13.13, new Node\Scalar\LNumber(13.13)];
                } elseif ($type === 'bool' || $type === '?bool') {
                    return [false, new Node\Expr\ConstFetch(
                        new Node\Name(
                            'false',
                        ),
                    )];
                } elseif ($type === 'string' || $type === '?string') {
                    if ($format === 'uri') {
                        return ['https://example.com/', new Node\Scalar\String_('https://example.com/')];
                    }
                    if ($format === 'date-time') {
                        return [date(\DateTimeInterface::RFC3339, 0), new Node\Scalar\String_(date(\DateTimeInterface::RFC3339, 0))];
                    }
                    if ($format === 'uuid') {
                        return ['4ccda740-74c3-4cfa-8571-ebf83c8f300a', new Node\Scalar\String_('4ccda740-74c3-4cfa-8571-ebf83c8f300a')];
                    }
                    if ($format === 'ipv4') {
                        return ['127.0.0.1', new Node\Scalar\String_('127.0.0.1')];
                    }
                    if ($format === 'ipv6') {
                        return ['::1', new Node\Scalar\String_('::1')];
                    }

                    return ['generated_' . ($parameter->format ?? 'null'), new Node\Scalar\String_('generated_' . ($parameter->format ?? 'null'))];
                }

                return [null, new Node\Expr\ConstFetch(
                    new Node\Name(
                        'null',
                    ),
                )];
            })($parameterType, $parameter->schema->format);

            $parameters[] = new Parameter(
                (new Convert($parameter->name))->toCamel(),
                $parameter->name,
                (string)$parameter->description,
                $parameterType,
                $parameter->schema->format,
                $parameter->in,
                $parameter->schema->default,
                $example,
                $exampleNode,
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
            $isError = $code >= 400;
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
                if (!$isError) {
                    $returnType[] = $responseClassname;
                }
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
