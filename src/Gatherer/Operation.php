<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Header;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationEmptyResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Parameter;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\Operation as openAPIOperation;
use CodeInc\HttpReasonPhraseLookup\HttpReasonPhraseLookup;
use Jawira\CaseConverter\Convert;
use Psr\Http\Message\ResponseInterface;

use function array_filter;
use function array_unique;
use function count;
use function implode;
use function is_array;
use function lcfirst;
use function Safe\preg_replace;
use function str_replace;
use function strlen;
use function strtoupper;
use function trim;
use function ucfirst;

final class Operation
{
    /**
     * @param array<string, mixed> $metaData
     */
    public static function gather(
        Namespace_ $baseNamespace,
        string $className,
        string $matchMethod,
        string $method,
        string $path,
        array $metaData,
        openAPIOperation $operation,
        ThrowableSchema $throwableSchemaRegistry,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation {
        $returnType = [];
        $parameters = [];
        $empties    = [];
        foreach ($operation->parameters as $parameter) {
            $parameterType = str_replace([
                'integer',
                'any',
                'boolean',
            ], [
                'int',
                'string|object',
                'bool',
            ], implode('|', is_array($parameter->schema->type) ? $parameter->schema->type : [$parameter->schema->type]));

            $parameters[] = new Parameter(
                (new Convert($parameter->name))->toCamel(),
                $parameter->name,
                $parameter->description ?? '',
                $parameterType,
                $parameter->schema->format,
                $parameter->in,
                $parameter->schema->default,
                ExampleData::scalarData(strlen($parameter->name), $parameterType, $parameter->schema->format),
            );
        }

        $classNameSanitized = str_replace('/', '\\', Utils::className($className));
        $requestBody        = [];
        if ($operation->requestBody !== null) {
            foreach ($operation->requestBody->content as $contentType => $requestBodyDetails) {
                $requestBodyClassname = $schemaRegistry->get(
                    $requestBodyDetails->schema,
                    $classNameSanitized . '\\Request\\' . Utils::className(str_replace('/', '_', $contentType)),
                );
                $requestBody[]        = new OperationRequestBody(
                    $contentType,
                    Schema::gather($baseNamespace, $requestBodyClassname, $requestBodyDetails->schema, $schemaRegistry),
                );
            }
        }

        $response = [];
        foreach ($operation->responses ?? [] as $code => $spec) {
            $isError      = $code >= 400;
            $contentCount = 0;
            foreach ($spec->content as $contentType => $contentTypeMediaType) {
                $contentCount++;
                $responseClassname = $schemaRegistry->get(
                    $contentTypeMediaType->schema,
                    'Operations\\' . $classNameSanitized . '\\Response\\' . Utils::className(
                        str_replace(
                            '/',
                            '_',
                            $contentType,
                        ) . '\\' . (HttpReasonPhraseLookup::getReasonPhrase($code) ?? 'Unknown')
                    ),
                );
                $response[]        = new OperationResponse(
                    $code,
                    $contentType,
                    $spec->description,
                    Type::gather(
                        $baseNamespace,
                        $responseClassname,
                        $contentType,
                        $contentTypeMediaType->schema,
                        true,
                        $schemaRegistry,
                    ),
                );
                if ($isError) {
                    $throwableSchemaRegistry->add('Schema\\' . $responseClassname);
                    continue;
                }

                $returnType[] = $responseClassname;
            }

            if ($contentCount !== 0) {
                continue;
            }

            $headers = [];
            foreach ($spec->headers as $headerName => $headerSpec) {
                $headers[$headerName] = new Header($headerName, Schema::gather(
                    $baseNamespace,
                    $schemaRegistry->get(
                        $headerSpec->schema,
                        'WebHookHeader\\' . ucfirst(preg_replace('/\PL/u', '', $headerName)),
                    ),
                    $headerSpec->schema,
                    $schemaRegistry
                ), ExampleData::determiteType($headerSpec->example));
            }

            $empties[] = new OperationEmptyResponse($code, $spec->description, $headers);
        }

        if (count($returnType) === 0) {
            $returnType[] = '\\' . ResponseInterface::class;
        }

        $name = lcfirst(trim(Utils::basename($className), '\\'));
        $group = strlen(trim(trim(Utils::dirname($className), '\\'), '.')) > 0 ? trim(Utils::dirname($className), '\\') : 'Fallback';

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation(
            ClassString::factory($baseNamespace, 'Operation\\' . Utils::fixKeyword($className)),
            ClassString::factory($baseNamespace, $classNameSanitized),
            ClassString::factory($baseNamespace, 'Operator\\' . Utils::fixKeyword($className)),
            $name,
            (new Convert($name))->toCamel(),
            $group,
            (new Convert($group))->toCamel(),
            $operation->operationId,
            strtoupper($matchMethod),
            strtoupper($method),
            $operation->summary,
            $operation->externalDocs,
            $path,
            $metaData,
            array_unique($returnType),
            [
                ...array_filter($parameters, static fn (Parameter $parameter): bool => $parameter->default === null),
                ...array_filter($parameters, static fn (Parameter $parameter): bool => $parameter->default !== null),
            ],
            $requestBody,
            $response,
            $empties,
        );
    }
}
