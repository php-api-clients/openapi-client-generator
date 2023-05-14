<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Header;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRedirect;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Parameter;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\Operation as openAPIOperation;
use CodeInc\HttpReasonPhraseLookup\HttpReasonPhraseLookup;
use DateTimeInterface;
use Jawira\CaseConverter\Convert;
use PhpParser\Node;
use Psr\Http\Message\ResponseInterface;

use function array_filter;
use function array_unique;
use function count;
use function implode;
use function is_array;
use function lcfirst;
use function Safe\date;
use function Safe\preg_replace;
use function str_replace;
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
        $redirects  = [];
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
                }

                if ($type === 'float' || $type === '?float' || $type === 'int|float' || $type === 'null|int|float') {
                    return [13.13, new Node\Scalar\DNumber(13.13)];
                }

                if ($type === 'bool' || $type === '?bool') {
                    return [
                        false,
                        new Node\Expr\ConstFetch(
                            new Node\Name(
                                'false',
                            ),
                        ),
                    ];
                }

                if ($type === 'string' || $type === '?string') {
                    if ($format === 'uri') {
                        return ['https://example.com/', new Node\Scalar\String_('https://example.com/')];
                    }

                    if ($format === 'date-time') {
                        return [date(DateTimeInterface::RFC3339, 0), new Node\Scalar\String_(date(DateTimeInterface::RFC3339, 0))];
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

                    return ['generated', new Node\Scalar\String_('generated')];
                }

                return [
                    null,
                    new Node\Expr\ConstFetch(
                        new Node\Name(
                            'null',
                        ),
                    ),
                ];
            })($parameterType, $parameter->schema->format);

            $parameters[] = new Parameter(
                (new Convert($parameter->name))->toCamel(),
                $parameter->name,
                $parameter->description ?? '',
                $parameterType,
                $parameter->schema->format,
                $parameter->in,
                $parameter->schema->default,
                $example,
                $exampleNode,
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
            $isError = $code >= 400;
            foreach ($spec->content as $contentType => $contentTypeMediaType) {
                $responseClassname = $schemaRegistry->get(
                    $contentTypeMediaType->schema,
                    'Operations\\' . $classNameSanitized . '\\Response\\' . Utils::className(
                        str_replace(
                            '/',
                            '_',
                            $contentType,
                        ) . '\\' .  HttpReasonPhraseLookup::getReasonPhrase($code) ?? 'Unknown'
                    ),
                );
                $response[]        = new OperationResponse(
                    $code,
                    $contentType,
                    $spec->description,
                    Schema::gather(
                        $baseNamespace,
                        $responseClassname,
                        $contentTypeMediaType->schema,
                        $schemaRegistry,
                    ),
                );
                if ($isError) {
                    $throwableSchemaRegistry->add('Schema\\' . $responseClassname);
                    continue;
                }

                $returnType[] = $responseClassname;
            }

            if ($code < 300 || $code >= 400) {
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
                ));
            }

            if (count($headers) <= 0) {
                continue;
            }

            $redirects[] = new OperationRedirect($code, $spec->description, $headers);
        }

        if (count($returnType) === 0) {
            $returnType[] = '\\' . ResponseInterface::class;
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\Operation(
            ClassString::factory($baseNamespace, 'Operation\\' . Utils::fixKeyword($className)),
            ClassString::factory($baseNamespace, $classNameSanitized),
            ClassString::factory($baseNamespace, 'Operator\\' . Utils::fixKeyword($className)),
            lcfirst(trim(Utils::basename($className), '\\')),
            trim(Utils::dirname($className), '\\'),
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
