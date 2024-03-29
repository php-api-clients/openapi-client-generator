<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use cebe\openapi\spec\ExternalDocumentation;

final readonly class Operation
{
    /**
     * @param array<mixed>                  $metaData
     * @param array<string>                 $returnType
     * @param array<Parameter>              $parameters
     * @param array<OperationRequestBody>   $requestBody
     * @param array<OperationResponse>      $response
     * @param array<OperationEmptyResponse> $empty
     */
    public function __construct(
        public ClassString $className,
        public ClassString $classNameSanitized,
        public ClassString $operatorClassName,
        public string $operatorLookUpMethod,
        public string $name,
        public string $nameCamel,
        public string|null $group,
        public string|null $groupCamel,
        public string $operationId,
        public string $matchMethod,
        public string $method,
        public string $summary,
        public ExternalDocumentation|null $externalDocs,
        public string $path,
        /** @var array<mixed> $metaData */
        public array $metaData,
        /** @var array<string> $returnType */
        public array $returnType,
        /** @var array<Parameter> $parameters */
        public array $parameters,
        /** @var array<OperationRequestBody> $requestBody */
        public array $requestBody,
        /** @var array<OperationResponse> $response */
        public array $response,
        /** @var array<OperationEmptyResponse> $empty */
        public array $empty,
    ) {
    }
}
