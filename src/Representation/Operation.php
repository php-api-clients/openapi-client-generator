<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;

final class Operation
{
    /**
     * @param array<mixed>                $metaData
     * @param array<string>               $returnType
     * @param array<Parameter>            $parameters
     * @param array<OperationRequestBody> $requestBody
     * @param array<OperationResponse>    $response
     * @param array<OperationRedirect>    $redirect
     */
    public function __construct(
        public readonly ClassString $className,
        public readonly ClassString $classNameSanitized,
        public readonly ClassString $operatorClassName,
        public readonly string $name,
        public readonly string $group,
        public readonly string $operationId,
        public readonly string $matchMethod,
        public readonly string $method,
        public readonly string $path,
        /** @var array<mixed> $metaData */
        public readonly array $metaData,
        /** @var array<string> $returnType */
        public readonly array $returnType,
        /** @var array<Parameter> $parameters */
        public readonly array $parameters,
        /** @var array<OperationRequestBody> $requestBody */
        public readonly array $requestBody,
        /** @var array<OperationResponse> $response */
        public readonly array $response,
        /** @var array<OperationRedirect> $redirect */
        public readonly array $redirect,
    ) {
    }
}
