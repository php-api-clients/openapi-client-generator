<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use cebe\openapi\spec\Schema as baseSchema;

final class Schema
{
    /**
     * @param array<Contract> $contracts
     * @param array<mixed>    $example
     * @param array<Property> $properties
     * @param array<string>   $type
     */
    public function __construct(
        public readonly ClassString $className,
        /** @var array<Contract> $contracts */
        public readonly array $contracts,
        public readonly ClassString $errorClassName,
        public readonly ClassString $errorClassNameAliased,
        public readonly string $title,
        public readonly string $description,
        /** @var array<mixed> $example */
        public readonly array $example,
        /** @var array<Property> $properties */
        public readonly array $properties,
        public readonly baseSchema $schema,
        public readonly bool $isArray,
        public readonly array $type,
    ) {
    }
}
