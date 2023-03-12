<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class Operation
{
    public function __construct(
        public readonly string $className,
        public readonly string $classNameSanitized,
        public readonly string $name,
        public readonly string $group,
        public readonly string $operationId,
        public readonly string $matchMethod,
        public readonly string $method,
        public readonly string $path,
        public readonly array  $metaData,
        /** @var array<string> $returnType */
        public readonly array  $returnType,
        /** @var array<Parameter> $parameters */
        public readonly array  $parameters,
        /** @var array<OperationRequestBody> $requestBody */
        public readonly array  $requestBody,
        /** @var array<OperationResponse> $response */
        public readonly array  $response,
        /** @var array<OperationRedirect> $redirect */
        public readonly array  $redirect,
    ){
    }
}
